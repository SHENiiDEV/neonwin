<?php

namespace App\Services;

use App\Models\AdminAudit;
use App\Models\Game;
use App\Models\NexusOperation;
use App\Models\OperationalAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NexusOperations
{
    public function __construct(private NexusGgrService $nexus) {}

    public function create(User $actor, array $data): NexusOperation
    {
        if (config('gaming.provider') !== 'nexus') {
            throw ValidationException::withMessages(['method' => 'Nexus operations are disabled after the provider switch.']);
        }
        $method = $data['method'];
        $users = User::whereIn('user_code', $data['user_codes'])->get();
        if ($users->count() !== count(array_unique($data['user_codes']))) {
            throw ValidationException::withMessages(['user_codes' => 'Every player code must belong to an existing account.']);
        }
        $isRtp = in_array($method, ['control_rtp', 'control_users_rtp'], true);
        if (! config('nexus.eur_agent_confirmed') || ! config($isRtp ? 'nexus.rtp_enabled' : 'nexus.rewards_enabled')) {
            throw ValidationException::withMessages(['method' => 'This capability has not been enabled for this agent.']);
        }
        $payload = [];
        if ($isRtp) {
            $rtp = (float) $data['rtp'];
            $agentType = config('nexus.agent_type');
            if ($rtp < 0 || $rtp > 999 || ($agentType === 'normal' && $rtp > 99) || ($agentType === 'test' && $rtp < 120) || ! in_array($agentType, ['normal', 'test', 'promo'], true)) {
                throw ValidationException::withMessages(['rtp' => 'RTP is outside this agent’s permitted range.']);
            }
            $payload['rtp'] = $rtp;
            if ($method === 'control_users_rtp') {
                $payload['user_codes'] = json_encode(array_values($data['user_codes']), JSON_THROW_ON_ERROR);
            } else {
                if (count($data['user_codes']) !== 1 || ! in_array($data['provider_code'], config('nexus.rtp_providers'), true)) {
                    throw ValidationException::withMessages(['provider_code' => 'Select one player and an enabled RTP provider.']);
                }
                $payload += ['user_code' => $users->first()->user_code, 'provider_code' => $data['provider_code']];
            }
        } else {
            if (count($data['user_codes']) !== 1 || $data['provider_code'] !== 'PRAGMATIC') {
                throw ValidationException::withMessages(['provider_code' => 'Rewards support one player and PRAGMATIC only.']);
            }
            $game = Game::where('provider_code', 'PRAGMATIC')->where('game_code', $data['game_code'])->where('status', true)->first();
            if (! $game || ! $game->provider?->status) {
                throw ValidationException::withMessages(['game_code' => 'Select an available PRAGMATIC game.']);
            }
            $amount = EuroMoney::cents($data['amount']);
            if ($amount < 1) {
                throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
            }
            $payload = ['user_code' => $users->first()->user_code, 'provider_code' => 'PRAGMATIC', 'game_code' => $game->game_code, 'amount' => $amount / 100];
            if ($method === 'bonus_create') {
                $payload += ['bonus_id' => $data['request_id'], 'max_bet' => (int) $data['max_bet']];
            } else {
                if (! isset(($game->bet_levels ?? [])[$data['bet_level']])) {
                    throw ValidationException::withMessages(['bet_level' => 'Choose a bet level supplied by the synchronized game catalog.']);
                }
                $payload += ['tour_id' => $data['request_id'], 'bet_level' => (int) $data['bet_level'], 'spin_count' => (int) $data['spin_count'], 'expiration_time' => Carbon::parse($data['expiration_time'])->utc()->toISOString()];
            }
        }
        $payload = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $operation = DB::transaction(function () use ($data, $actor, $users, $method, $payload): NexusOperation {
            $existing = NexusOperation::where('external_id', $data['request_id'])->lockForUpdate()->first();
            if ($existing) {
                abort_unless($existing->actor_id === $actor->id && $existing->method === $method && $existing->request === $payload, 409, 'Request identifier already used.');

                return $existing;
            }
            if ($method === 'tour_create') {
                User::whereKey($users->first()->id)->lockForUpdate()->firstOrFail();
                if (NexusOperation::where('active_tour_key', $users->first()->id.':PRAGMATIC:'.$data['game_code'])->exists()) {
                    throw ValidationException::withMessages(['game_code' => 'This player already has an active or unresolved tour on this game.']);
                }
            }
            $operation = NexusOperation::create([
                'external_id' => $data['request_id'], 'actor_id' => $actor->id, 'user_id' => $users->count() === 1 ? $users->first()->id : null,
                'method' => $method, 'provider_code' => $data['provider_code'] ?? null, 'game_code' => $data['game_code'] ?? null,
                'request' => $payload, 'active_tour_key' => $method === 'tour_create' ? $users->first()->id.':PRAGMATIC:'.$data['game_code'] : null,
                'expires_at' => $method === 'tour_create' ? $data['expiration_time'] : null,
            ]);
            $before = NexusOperation::where('method', $method)->where('status', 'succeeded')->where('provider_code', $operation->provider_code)->where('user_id', $operation->user_id)->where('id', '<', $operation->id)->latest('id')->get()->first(function (NexusOperation $previous) use ($method, $payload): bool {
                if ($method !== 'control_users_rtp') {
                    return true;
                }

                $previousCodes = json_decode($previous->request['user_codes'] ?? '[]', true);
                $currentCodes = json_decode($payload['user_codes'], true);
                sort($previousCodes);
                sort($currentCodes);

                return $previousCodes === $currentCodes;
            });
            AdminAudit::create(['actor_id' => $actor->id, 'action' => $method.'_requested', 'target' => $operation->external_id, 'before' => $before?->request, 'after' => $payload]);

            return $operation;
        }, 3);
        // A durable claim prevents repeated browser submissions from sending a second create.
        $claimed = NexusOperation::whereKey($operation->id)->where('status', 'pending')->update(['status' => 'sending']);
        if (! $claimed) {
            return $operation->refresh();
        }
        $result = $this->nexus->request($method, $payload);

        return $this->complete($operation, $actor, $result, $isRtp ? 'succeeded' : ($method === 'bonus_create' ? 'waiting' : 'active'));
    }

    public function cancel(User $actor, NexusOperation $operation): NexusOperation
    {
        if ($operation->method !== 'tour_create') {
            throw ValidationException::withMessages(['operation' => 'Bonus cancellation requires the authoritative cancel-bonus contract. Use Nexus back office for now.']);
        }
        $claimed = NexusOperation::whereKey($operation->id)->whereIn('status', ['active', 'running', 'unknown'])->update(['status' => 'canceling']);
        if (! $claimed) {
            throw ValidationException::withMessages(['operation' => 'This tour cannot be canceled in its current state. Refresh and reconcile unresolved operations.']);
        }
        AdminAudit::create(['actor_id' => $actor->id, 'action' => 'tour_cancel_requested', 'target' => $operation->external_id, 'before' => ['status' => $operation->status], 'after' => ['tour_id' => $operation->external_id]]);
        $result = $this->nexus->request('tour_cancel', ['tour_id' => $operation->external_id]);

        return $this->complete($operation, $actor, $result, 'canceled', true);
    }

    private function complete(NexusOperation $operation, User $actor, array $result, string $success, bool $cancel = false): NexusOperation
    {
        return DB::transaction(function () use ($operation, $actor, $result, $success, $cancel): NexusOperation {
            $locked = NexusOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $status = NexusGgrService::successful($result) ? $success : (($result['unknown'] ?? false) ? 'unknown' : ($cancel ? 'needs_review' : 'failed'));
            if (in_array($locked->status, ['finished', 'running'], true) && ! $cancel) {
                $status = $locked->status;
            }
            $locked->update(['status' => $status, 'result' => $result, 'active_tour_key' => in_array($status, ['failed', 'finished', 'canceled'], true) ? null : $locked->active_tour_key]);
            AdminAudit::create(['actor_id' => $actor->id, 'action' => $cancel ? 'tour_cancel_result' : $locked->method.'_result', 'target' => $locked->external_id, 'after' => ['status' => $status, 'result' => $result]]);
            if (in_array($status, ['unknown', 'needs_review', 'failed'], true)) {
                OperationalAlert::create(['kind' => 'nexus_operation', 'reference' => $locked->external_id, 'message' => $result['msg'] ?? 'Reconcile this operation in Nexus back office.']);
            }

            return $locked;
        });
    }
}
