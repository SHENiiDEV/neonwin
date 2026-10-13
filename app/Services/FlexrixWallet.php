<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameTransaction;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class FlexrixWallet
{
    public function handle(string $channel, array $body, string $action): array
    {
        $allowed = $channel === 'casino' ? ['balance', 'bet', 'win', 'refund', 'rollback'] : ['balance', 'debit', 'credit', 'rollback'];
        if (! in_array($action, $allowed, true)) {
            throw new InvalidArgumentException('Unknown action.');
        }
        $player = $body[$channel === 'casino' ? 'player_id' : 'external_player_id'] ?? null;
        if (! is_string($player) || strlen($player) > 255) {
            throw new InvalidArgumentException('Invalid player.');
        }
        if (($body['currency'] ?? ($action === 'balance' ? 'EUR' : '')) !== 'EUR') {
            return ['ok' => false, 'error_code' => 'CURRENCY_NOT_SUPPORTED'];
        }
        $user = User::where('user_code', $player)->first();
        if (! $user) {
            return ['ok' => false, 'error_code' => 'UNKNOWN_PLAYER'];
        }

        return DB::transaction(function () use ($user, $channel, $body, $action): array {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($action === 'balance') {
                return ['ok' => true, 'balance' => $user->eur_balance_cents / 100, 'currency' => 'EUR'];
            }
            $scope = hash('sha256', $channel.':'.config('gaming.flexrix.'.$channel.'_key'));
            $id = $this->identifier($channel === 'sportsbook' && $action === 'rollback' ? 'rollback:'.$this->identifier($body['original_transaction_id'] ?? null) : ($body['transaction_id'] ?? null));
            $amount = EuroMoney::cents($body['amount'] ?? null);
            $reversals = [];
            if ($action === 'refund') {
                $reversals[] = ['transaction_id' => $this->identifier($body['ref_transaction_id'] ?? null), 'action' => 'bet', 'amount' => $body['amount']];
            } elseif ($action === 'rollback') {
                if ($channel === 'sportsbook') {
                    $reversals[] = ['transaction_id' => $this->identifier($body['original_transaction_id'] ?? null), 'action' => 'debit', 'amount' => $body['amount']];
                } else {
                    $reversals = $body['rollback_transactions'] ?? null;
                    if (is_string($reversals)) {
                        $reversals = json_decode($reversals, true);
                    }
                    if (! is_array($reversals) || ! array_is_list($reversals) || count($reversals) < 1 || count($reversals) > 100) {
                        throw new InvalidArgumentException('Rollback needs original transactions.');
                    }
                }
            }
            $adjustment = $amount;
            if ($channel === 'sportsbook' && $action === 'credit' && filter_var($body['settlement']['is_correction'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $value = $body['settlement']['adjustment'] ?? null;
                if (! is_scalar($value) || is_bool($value)) {
                    throw new InvalidArgumentException('Invalid correction.');
                }
                $adjustment = str_starts_with((string) $value, '-') ? -EuroMoney::cents(substr((string) $value, 1)) : EuroMoney::cents($value);
            }
            $fingerprint = $this->fingerprint($user, $action, $amount, $adjustment, $body, $reversals);
            $existing = DB::table('gaming_callbacks')->where('scope', $scope)->where('remote_id', $id)->first();
            if ($existing) {
                if ($existing->user_id !== $user->id) {
                    throw new InvalidArgumentException('Conflicting transaction identifier.');
                }
                if ($existing->fingerprint !== null && ! hash_equals($existing->fingerprint, $fingerprint)) {
                    $original = $existing->game_transaction_id ? GameTransaction::find($existing->game_transaction_id) : null;
                    $originalBody = $original?->raw_payload;
                    $legacyFingerprint = is_array($originalBody)
                        ? $this->fingerprint($user, $action, $amount, $adjustment, $body, $reversals, (string) ($originalBody['round_id'] ?? $originalBody['bet_id'] ?? ''))
                        : null;
                    if ($legacyFingerprint === null || ! hash_equals($existing->fingerprint, $legacyFingerprint)) {
                        throw new InvalidArgumentException('Conflicting transaction identifier.');
                    }
                }

                return json_decode($existing->response, true, flags: JSON_THROW_ON_ERROR);
            }
            $before = $user->eur_balance_cents;
            $bet = in_array($action, ['bet', 'debit'], true) ? $amount : 0;
            $win = in_array($action, ['win', 'credit'], true) ? $adjustment : 0;
            $delta = $win - $bet;
            $originals = [];
            foreach ($reversals as $reversal) {
                if (! is_array($reversal)) {
                    throw new InvalidArgumentException('Invalid original transaction.');
                }
                $remote = $this->identifier($reversal['transaction_id'] ?? null);
                if ($remote === $id) {
                    throw new InvalidArgumentException('Self reversal.');
                }
                $original = DB::table('gaming_callbacks')->where('scope', $scope)->where('remote_id', $remote)->first();
                $expectedAction = $reversal['action'] ?? null;
                if (! in_array($expectedAction, ['bet', 'debit', 'win', 'credit'], true)) {
                    throw new InvalidArgumentException('Invalid reversal action.');
                }
                $originalAmount = EuroMoney::cents($reversal['amount'] ?? null);
                if (! $original) {
                    $this->receipt($scope, $remote, $user, 'canceled', 0, null, ['ok' => false, 'error_code' => 'TRANSACTION_CANCELED']);

                    continue;
                }
                if ($original->user_id !== $user->id || ($original->action !== 'canceled' && $original->action !== $expectedAction) || ($original->game_transaction_id && abs($original->delta_cents) !== $originalAmount)) {
                    throw new InvalidArgumentException('Original transaction mismatch.');
                }
                if ($original->reversed_at || isset($originals[$original->id])) {
                    continue;
                }
                $originals[$original->id] = $original;
                if (in_array($original->action, ['bet', 'debit'], true)) {
                    $bet += $original->delta_cents;
                } else {
                    $win -= $original->delta_cents;
                }
                $delta -= $original->delta_cents;
            }
            if ($bet > $before) {
                $response = ['ok' => false, 'error_code' => 'INSUFFICIENT_FUNDS'];
                $this->receipt($scope, $id, $user, $action, 0, $fingerprint, $response);

                return $response;
            }
            if ($before + $delta < 0) {
                throw new RuntimeException('A reversal exceeds available funds and requires reconciliation.');
            }
            $game = $channel === 'casino' ? $this->game($body['game_uuid'] ?? null) : null;
            if (! $game && $channel === 'casino') {
                foreach ($originals as $original) {
                    if ($original->game_transaction_id) {
                        $originalTransaction = GameTransaction::findOrFail($original->game_transaction_id);
                        $game = Game::where('provider_code', $originalTransaction->provider_code)->where('game_code', $originalTransaction->game_code)->first();
                        break;
                    }
                }
            }
            if ($channel === 'casino' && ! $game && in_array($action, ['bet', 'win'], true)) {
                throw new InvalidArgumentException('Unknown game UUID.');
            }
            $user->eur_balance_cents += $delta;
            $user->save();
            $hash = hash('sha256', $scope.':'.$id);
            $transaction = GameTransaction::create([
                'transaction_id' => 'flexrix:'.$hash, 'agent_code' => 'flexrix:'.$scope, 'user_id' => $user->id, 'provider_code' => $game?->provider_code ?? ($channel === 'casino' ? 'FLEXRIX_CASINO' : 'FLEXRIX_SPORTSBOOK'),
                'game_code' => $game?->game_code ?? '', 'remote_transaction_id' => $id, 'game_type' => $channel === 'sportsbook' ? 'SB' : ($game?->category === 'live' ? 'live' : 'slot'),
                'round_id' => $this->identifier((string) ($body['round_id'] ?? $body['bet_id'] ?? $id)), 'currency' => 'EUR', 'fingerprint' => $fingerprint,
                'type' => $action, 'bet_amount' => $bet < 0 ? '-'.EuroMoney::decimal(-$bet) : EuroMoney::decimal($bet), 'win_amount' => $win < 0 ? '-'.EuroMoney::decimal(-$win) : EuroMoney::decimal($win),
                'balance_before' => EuroMoney::decimal($before), 'balance_after' => EuroMoney::decimal($user->eur_balance_cents), 'raw_payload' => $body,
            ]);
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'flexrix:'.$hash, 'kind' => 'gaming_'.$action, 'delta_cents' => $delta, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
            foreach ($originals as $original) {
                DB::table('gaming_callbacks')->where('id', $original->id)->update(['reversed_at' => now()]);
                if ($original->game_transaction_id) {
                    app(VipService::class)->reverseBet($user, $original->game_transaction_id);
                    app(LaunchRakebackService::class)->reverseTransaction($user, $original->game_transaction_id);
                }
            }
            if ($channel === 'sportsbook' && $action === 'credit' && in_array($body['reason'] ?? '', ['void_settlement', 'void_after_partial_cashout'], true)) {
                $originalBet = GameTransaction::where('agent_code', 'flexrix:'.$scope)->where('user_id', $user->id)->where('round_id', (string) ($body['bet_id'] ?? ''))->where('type', 'debit')->first();
                if ($originalBet) {
                    app(VipService::class)->reverseBet($user, $originalBet->id, $body['reason'] === 'void_after_partial_cashout' ? $amount : null);
                }
            }
            if ($bet > 0 && empty($body['freespin_id']) && ! filter_var($body['is_freebet'] ?? false, FILTER_VALIDATE_BOOLEAN) && ($body['type'] ?? '') !== 'freespin') {
                app(VipService::class)->recordBet($user, $transaction, $bet, []);
            }
            if (empty($originals) && empty($body['freespin_id']) && ! filter_var($body['is_freebet'] ?? false, FILTER_VALIDATE_BOOLEAN) && ($body['type'] ?? '') !== 'freespin') {
                $void = $channel === 'sportsbook' && in_array($body['reason'] ?? '', ['void_settlement', 'void_after_partial_cashout'], true);
                app(LaunchRakebackService::class)->record($user, $transaction, max(0, $bet), $void ? 0 : $win);
            }
            GameSession::where('user_id', $user->id)->where('provider_code', $game?->provider_code ?? ($channel === 'casino' ? 'FLEXRIX_CASINO' : 'FLEXRIX_SPORTSBOOK'))->update(['last_activity_at' => now()]);
            $response = ['ok' => true, 'balance' => $user->eur_balance_cents / 100, 'currency' => 'EUR'];
            $this->receipt($scope, $id, $user, $action, $delta, $fingerprint, $response, $transaction->id);

            return $response;
        }, 3);
    }

    private function game(mixed $uuid): ?Game
    {
        if (! is_string($uuid)) {
            return null;
        }
        $slug = str_starts_with($uuid, 'flexrix/') ? substr($uuid, 8) : null;

        return Game::where('integration', 'flexrix')->where(fn ($query) => $query->where('game_code', $uuid)->when($slug !== null, fn ($query) => $query->orWhere('external_code', $slug)))->first();
    }

    private function identifier(mixed $value): string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 255 || preg_match('/[\x00-\x1f]/', $value)) {
            throw new InvalidArgumentException('Invalid transaction identifier.');
        }

        return $value;
    }

    private function fingerprint(User $user, string $action, int $amount, int $adjustment, array $body, array $reversals, ?string $legacyRoundId = null): string
    {
        $fields = [$user->id, $action, $amount, $adjustment, $body['game_uuid'] ?? null];
        if ($legacyRoundId !== null) {
            $fields[] = $legacyRoundId;
        }
        array_push($fields, $reversals, $body['type'] ?? null, $body['freespin_id'] ?? null);

        return hash('sha256', json_encode($fields, JSON_THROW_ON_ERROR));
    }

    private function receipt(string $scope, string $id, User $user, string $action, int $delta, ?string $fingerprint, array $response, ?int $transactionId = null): void
    {
        DB::table('gaming_callbacks')->insert(['scope' => $scope, 'remote_id' => $id, 'user_id' => $user->id, 'game_transaction_id' => $transactionId, 'action' => $action, 'delta_cents' => $delta, 'fingerprint' => $fingerprint, 'response' => json_encode($response, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }
}
