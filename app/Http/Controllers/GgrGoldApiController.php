<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\GameTransaction;
use App\Models\NexusOperation;
use App\Models\OperationalAlert;
use App\Models\User;
use App\Models\WalletEntry;
use App\Services\EuroMoney;
use App\Services\LaunchRakebackService;
use App\Services\VipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GgrGoldApiController extends Controller
{
    public function handleCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $secret = config('nexus.agent_secret');
        $agent = config('nexus.agent_code');
        if (! is_string($secret) || $secret === '' || ! is_string($agent) || $agent === '' || ! is_string($payload['agent_secret'] ?? null) || ! is_string($payload['agent_code'] ?? null) || ! hash_equals($secret, $payload['agent_secret']) || ! hash_equals($agent, $payload['agent_code'])) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_AGENT'], 403);
        }
        if (! config('nexus.eur_agent_confirmed')) {
            return response()->json(['status' => 0, 'msg' => 'EUR_CONNECTION_UNAVAILABLE'], 503);
        }
        $method = $payload['method'] ?? '';
        if (! in_array($method, ['transaction', 'user_balance'], true)) {
            return response()->json(['status' => 0, 'msg' => 'UNKNOWN_METHOD'], 400);
        }
        $validator = Validator::make($payload, ['user_code' => ['required', 'string', 'max:255'], 'user_token' => [$method === 'user_balance' ? 'required' : 'nullable', 'string', 'max:2048']]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_USER_REQUEST'], 422);
        }
        $user = User::where('user_code', $payload['user_code'])->first();
        if (! $user) {
            return response()->json(['status' => 0, 'msg' => 'USER_NOT_FOUND'], 404);
        }
        $session = null;
        if (! empty($payload['user_token'])) {
            $session = GameSession::where('user_id', $user->id)->where('token_hash', hash('sha256', $payload['user_token']))->first();
            if (! $session) {
                return response()->json(['status' => 0, 'msg' => 'INVALID_USER_TOKEN'], 403);
            }
        }
        if ($method === 'user_balance' && config('gaming.provider') !== 'nexus') {
            return response()->json(['status' => 0, 'msg' => 'PROVIDER_RETIRED'], 503);
        }
        if ($method === 'user_balance') {
            if (! empty($payload['game_code']) && $session->game_code !== $payload['game_code'] && ! ($session->provider_code === 'SPORTSBOOK' && $payload['game_code'] === 'SPORTSBOOK')) {
                return response()->json(['status' => 0, 'msg' => 'INVALID_USER_TOKEN'], 403);
            }

            return $this->balance($user);
        }
        $type = $payload['game_type'] ?? '';
        if (! is_string($type) || ! in_array($type, ['slot', 'live', 'SB', 'MN', 'FH'], true) || ! is_array($payload[$type] ?? null)) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_GAME_TYPE'], 422);
        }
        $detail = $payload[$type];
        $validator = Validator::make($detail, [
            'provider_code' => ['required', 'string', 'max:100'], 'game_code' => ['present', 'nullable', 'string', 'max:255'],
            'round_id' => ['required', 'regex:/^[0-9]+$/', 'max:100'], 'txn_id' => ['required', 'string', 'max:255'],
            'txn_id_v2' => ['nullable', 'string', 'max:255'], 'txn_type' => ['required', 'in:debit,credit,debit_credit'],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_TRANSACTION'], 422);
        }
        $id = $detail['txn_id_v2'] ?? null;
        if (! $id) {
            if (now('UTC')->lt('2026-10-15 00:00:00') || $detail['txn_id'] === (string) $detail['round_id']) {
                return response()->json(['status' => 0, 'msg' => 'UNIQUE_TRANSACTION_ID_REQUIRED'], 422);
            }
            $id = $detail['txn_id'];
        }
        if ($session && ($session->provider_code !== $detail['provider_code'] || ($session->game_code !== '' && $session->game_code !== ($detail['game_code'] ?? '')))) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_SESSION_GAME'], 403);
        }
        try {
            $bet = $detail['txn_type'] === 'credit' ? 0 : EuroMoney::cents($detail['bet_money'] ?? null);
            $win = $detail['txn_type'] === 'debit' ? 0 : EuroMoney::cents($detail['win_money'] ?? null);
            if (! empty($detail['tour_id']) && $bet !== 0) {
                return response()->json(['status' => 0, 'msg' => 'FREE_TOUR_BET_MUST_BE_ZERO'], 422);
            }
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['status' => 0, 'msg' => 'INVALID_AMOUNT'], 422);
        }
        $fingerprint = hash('sha256', json_encode([$type, $detail['game_code'] ?? '', (string) $detail['round_id'], $detail['txn_type'], $bet, $win]));
        try {
            $result = DB::transaction(function () use ($user, $agent, $id, $detail, $bet, $win, $type, $fingerprint, $session, $payload): array {
                $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existing = GameTransaction::where('agent_code', $agent)->where('user_id', $user->id)->where('provider_code', $detail['provider_code'])->where('remote_transaction_id', $id)->first();
                if ($existing) {
                    if ($existing->fingerprint !== $fingerprint) {
                        OperationalAlert::create(['kind' => 'callback_conflict', 'reference' => $existing->transaction_id, 'message' => 'A duplicate identifier carried different financial details. Reconcile with Nexus.']);

                        return ['status' => 0, 'msg' => 'TRANSACTION_CONFLICT', 'http' => 409];
                    }

                    return ['status' => 1, 'user_balance' => $locked->eur_balance_cents / 100, 'msg' => 'TRANSACTION_ALREADY_PROCESSED'];
                }
                if (config('gaming.provider') !== 'nexus' && ($bet > 0 || ! GameTransaction::where('agent_code', $agent)->where('user_id', $user->id)->where('provider_code', $detail['provider_code'])->where('round_id', (string) $detail['round_id'])->exists())) {
                    return ['status' => 0, 'msg' => 'PROVIDER_RETIRED', 'http' => 409];
                }
                $before = $locked->eur_balance_cents;
                if ($bet > $before) {
                    return ['status' => 0, 'msg' => 'INSUFFICIENT_USER_FUNDS'];
                }
                $after = $before - $bet + $win;
                $key = hash('sha256', json_encode([$agent, $user->id, $detail['provider_code'], $id]));
                $locked->eur_balance_cents = $after;
                $locked->save();
                $safe = array_intersect_key($detail, array_flip(['provider_code', 'game_code', 'type', 'round_id', 'txn_id', 'txn_id_v2', 'txn_type', 'bet_money', 'win_money', 'is_end', 'bonus_id', 'bonus_total_win', 'bonus_end', 'tour_id', 'tour_rest_count', 'tour_total_win', 'tour_end']));
                if (is_string($payload['info'] ?? null)) {
                    $info = json_decode($payload['info'], true);
                    if (is_array($info)) {
                        $safe['info'] = array_intersect_key($info, array_flip(['couponCode', 'status', 'totalOdds', 'potentialWin', 'stake', 'payout', 'betslips']));
                    }
                }
                $transaction = GameTransaction::create([
                    'transaction_id' => $key, 'remote_transaction_id' => $id, 'agent_code' => $agent, 'user_id' => $user->id,
                    'provider_code' => $detail['provider_code'], 'game_code' => $detail['game_code'] ?? '', 'game_type' => $type,
                    'round_id' => (string) $detail['round_id'], 'type' => $detail['txn_type'], 'currency' => 'EUR', 'fingerprint' => $fingerprint,
                    'bet_amount' => EuroMoney::decimal($bet), 'win_amount' => EuroMoney::decimal($win),
                    'balance_before' => EuroMoney::decimal($before), 'balance_after' => EuroMoney::decimal($after), 'raw_payload' => $safe,
                ]);
                WalletEntry::create(['user_id' => $user->id, 'reference' => 'game:'.$key, 'kind' => 'game', 'delta_cents' => $win - $bet, 'balance_before_cents' => $before, 'balance_after_cents' => $after]);
                app(VipService::class)->recordBet($locked, $transaction, $bet, $detail);
                if (empty($detail['tour_id']) && empty($detail['bonus_id'])) {
                    app(LaunchRakebackService::class)->record($locked, $transaction, $bet, $win);
                }
                GameSession::where('user_id', $user->id)->where('provider_code', $detail['provider_code'])->when($session, fn ($query) => $query->whereKey($session->id))->update(['last_activity_at' => now(), 'status' => 'recent_activity']);
                foreach (['bonus', 'tour'] as $reward) {
                    if (! empty($detail[$reward.'_id'])) {
                        $operation = NexusOperation::where('external_id', $detail[$reward.'_id'])->where('user_id', $user->id)->where('provider_code', $detail['provider_code'])->where('game_code', $detail['game_code'])->lockForUpdate()->first();
                        if ($operation) {
                            $previous = $operation->progress ?? [];
                            $progress = array_replace($previous, array_intersect_key($detail, array_flip([$reward.'_id', $reward.'_total_win', $reward.'_rest_count', $reward.'_end'])));
                            $progress[$reward.'_total_win'] = max((float) ($previous[$reward.'_total_win'] ?? 0), (float) ($progress[$reward.'_total_win'] ?? 0));
                            if (isset($previous[$reward.'_rest_count'], $progress[$reward.'_rest_count'])) {
                                $progress[$reward.'_rest_count'] = min($previous[$reward.'_rest_count'], $progress[$reward.'_rest_count']);
                            }
                            $ended = ($detail[$reward.'_end'] ?? false) || $operation->status === 'finished';
                            $progress[$reward.'_end'] = $ended;
                            $operation->update(['progress' => $progress, 'status' => $ended ? 'finished' : 'running', 'active_tour_key' => $ended ? null : $operation->active_tour_key]);
                        }
                    }
                }

                return ['status' => 1, 'user_balance' => $after / 100];
            }, 3);
            $http = $result['http'] ?? 200;
            unset($result['http']);

            return response()->json($result, $http);
        } catch (\Throwable $exception) {
            Log::error('Nexus settlement was not committed; reconciliation required.', ['reference' => hash('sha256', $agent.':'.$user->id.':'.$id), 'exception_type' => $exception::class]);
            try {
                OperationalAlert::create(['kind' => 'settlement_unknown', 'reference' => hash('sha256', $id), 'message' => 'Callback settlement failed. Compare the local ledger with Nexus game logs before resolving.']);
            } catch (\Throwable $alertException) {
                Log::error('Nexus settlement alert could not be persisted.');
            }

            return response()->json(['status' => 0, 'msg' => 'SETTLEMENT_UNKNOWN'], 503);
        }
    }

    private function balance(User $user): JsonResponse
    {
        return response()->json(['status' => 1, 'user_balance' => $user->eur_balance_cents / 100]);
    }
}
