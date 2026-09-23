<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameTransaction;
use App\Models\LiveCommunityWin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GgrGoldApiController extends Controller
{
    /**
     * Handle incoming webhook callbacks from NexusGGR aggregator
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $method = $payload['method'] ?? $request->input('action') ?? '';
        $agentSecret = $request->header('Agent-Secret', $payload['agent_secret'] ?? '');

        $configuredSecret = config('nexus.agent_secret', '5562754eb33e45937673a6bacdd8be9d');

        // Optional Secret validation
        if (! empty($configuredSecret) && ! empty($agentSecret) && $agentSecret !== $configuredSecret) {
            Log::warning('Nexus Callback: Invalid Agent Secret provided', [
                'provided' => $agentSecret,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 0,
                'msg' => 'INVALID_AGENT_SECRET',
            ], 403);
        }

        Log::info("🎯 [Nexus Callback IN] method=[{$method}]", [
            'method' => $method,
            'ip' => $request->ip(),
            'payload' => $payload,
        ]);

        switch ($method) {
            case 'user_balance':
            case 'balance':
                return $this->handleUserBalance($payload);

            case 'transaction':
            case 'bet_win':
            case 'change_balance':
                return $this->handleTransaction($payload);

            case 'refund':
            case 'rollback':
                return $this->handleRefund($payload);

            default:
                Log::warning("❓ [Nexus Callback] Unknown method: {$method}", ['payload' => $payload]);

                return response()->json([
                    'status' => 0,
                    'msg' => "UNKNOWN_METHOD_{$method}",
                ], 400);
        }
    }

    /**
     * A. Balance Request (method: user_balance)
     */
    protected function handleUserBalance(array $payload): JsonResponse
    {
        $userCode = $payload['user_code'] ?? $payload['user_id'] ?? '';
        $rate = (float) config('nexus.denomination_rate', 1);

        if (empty($userCode)) {
            Log::warning('⚠️ [Nexus user_balance] Missing user_code', ['payload' => $payload]);

            return response()->json([
                'status' => 0,
                'msg' => 'MISSING_USER_CODE',
            ], 400);
        }

        $user = User::where('user_code', $userCode)
            ->orWhere('id', is_numeric($userCode) ? (int) $userCode : 0)
            ->first();

        if (! $user) {
            Log::warning("⚠️ [Nexus user_balance] User not found: [{$userCode}]");

            return response()->json([
                'status' => 0,
                'msg' => 'USER_NOT_FOUND',
            ], 404);
        }

        $providerBalance = floor((((float) $user->game_balance) / $rate) * 100) / 100;

        Log::info("💰 [Nexus user_balance OUT] User=[{$userCode}] Balance=[{$user->game_balance} SC] (SentToSlot: {$providerBalance} SC)");

        return response()->json([
            'status' => 1,
            'user_balance' => (float) $providerBalance,
        ]);
    }


    /**
     * B. Process Bet and Win transactions (method: transaction)
     * Matches NexusGGR Social Casino specification with Denomination Gateway
     */
    protected function handleTransaction(array $payload): JsonResponse
    {
        $userCode = $payload['user_code'] ?? $payload['user_id'] ?? '';
        $gameType = $payload['game_type'] ?? 'slot';
        $rate = (float) config('nexus.denomination_rate', 1);

        // Extract nested game details object matching game_type (slot, live, SB, MN, FT)
        $gameData = $payload[$gameType] ?? $payload['slot'] ?? $payload['live'] ?? $payload['MN'] ?? $payload['SB'] ?? $payload;

        $providerCode = $gameData['provider_code'] ?? $payload['provider_code'] ?? 'PRAGMATIC';
        $gameCode = $gameData['game_code'] ?? $payload['game_code'] ?? '';
        $roundId = $gameData['round_id'] ?? $payload['round_id'] ?? null;
        $txnId = (string) ($gameData['txn_id'] ?? $payload['txn_id'] ?? $roundId ?? '');
        // CRUCIAL: Deduplicate on txn_id_v2 as specified by NexusGGR docs
        $txnIdV2 = (string) ($gameData['txn_id_v2'] ?? $payload['txn_id_v2'] ?? $txnId);
        $txnType = strtolower($gameData['txn_type'] ?? $payload['txn_type'] ?? 'debit_credit');

        $betMoney = (float) ($gameData['bet_money'] ?? $gameData['bet_amount'] ?? $payload['bet_amount'] ?? $payload['bet'] ?? 0);
        $winMoney = (float) ($gameData['win_money'] ?? $gameData['win_amount'] ?? $payload['win_amount'] ?? $payload['win'] ?? 0);

        // Adjust bet and win based on txn_type
        if ($txnType === 'debit') {
            $winMoney = 0.0;
        } elseif ($txnType === 'credit') {
            $betMoney = 0.0;
        }

        // Convert provider credits to internal SC balance
        $coinsToDebit = (float) round($betMoney * $rate, 4);
        $coinsToCredit = (float) round($winMoney * $rate, 4);

        Log::info("🎰 [Nexus transaction IN] User=[{$userCode}] Provider=[{$providerCode}] Game=[{$gameCode}] Type=[{$txnType}] Bet=[{$betMoney} SC -> {$coinsToDebit} SC] Win=[{$winMoney} SC -> {$coinsToCredit} SC] TxnId=[{$txnIdV2}]");

        if (empty($userCode)) {
            Log::warning('⚠️ [Nexus transaction] Missing user_code');

            return response()->json([
                'status' => 0,
                'msg' => 'MISSING_USER_CODE',
            ], 400);
        }

        if (empty($txnIdV2)) {
            Log::warning('⚠️ [Nexus transaction] Missing txn_id_v2');

            return response()->json([
                'status' => 0,
                'msg' => 'MISSING_TRANSACTION_ID',
            ], 400);
        }

        // Idempotency check on txn_id_v2
        $existingTx = GameTransaction::where('transaction_id', $txnIdV2)->first();
        if ($existingTx) {
            $user = User::find($existingTx->user_id);
            $currentProviderBalance = $user ? floor((((float) $user->game_balance) / $rate) * 100) / 100 : 0.0;

            Log::info("🔁 [Nexus transaction DUPLICATE] TxnId=[{$txnIdV2}] already processed. Returned Balance=[{$currentProviderBalance}]");

            return response()->json([
                'status' => 1,
                'user_balance' => (float) $currentProviderBalance,
                'msg' => 'TRANSACTION_ALREADY_PROCESSED',
            ]);
        }

        // Atomic DB transaction
        return DB::transaction(function () use ($userCode, $txnIdV2, $winMoney, $coinsToDebit, $coinsToCredit, $gameCode, $providerCode, $txnType, $payload, $rate) {
            $user = User::where('user_code', $userCode)
                ->orWhere('id', is_numeric($userCode) ? (int) $userCode : 0)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                Log::warning("⚠️ [Nexus transaction] User not found: [{$userCode}]");

                return response()->json([
                    'status' => 0,
                    'msg' => 'USER_NOT_FOUND',
                ], 404);
            }

            $currentBalance = (float) $user->game_balance;

            // Insufficient funds check
            if ($coinsToDebit > 0 && $currentBalance < $coinsToDebit) {
                Log::warning("⛔ [Nexus transaction INSUFFICIENT FUNDS] User=[{$userCode}] Balance=[{$currentBalance} SC] < Bet=[{$coinsToDebit} SC]");

                return response()->json([
                    'status' => 0,
                    'msg' => 'INSUFFICIENT_USER_FUNDS',
                ], 400);
            }

            $balanceBefore = $currentBalance;
            $balanceAfter = $currentBalance - $coinsToDebit + $coinsToCredit;
            $user->game_balance = $balanceAfter;

            // Award VIP XP points (1 SC wagered = 10 VIP XP)
            if ($coinsToDebit > 0) {
                $user->awardVipXp($coinsToDebit * 10);
            }

            $user->save();

            // Record transaction
            GameTransaction::create([
                'transaction_id' => $txnIdV2,
                'user_id' => $user->id,
                'game_code' => $gameCode,
                'provider_code' => $providerCode,
                'bet_amount' => $coinsToDebit,
                'win_amount' => $coinsToCredit,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'type' => $txnType,
                'raw_payload' => $payload,
            ]);

            // Live Community Wins feed for high multipliers
            if ($coinsToCredit > 0) {
                $multiplier = $coinsToDebit > 0 ? round($coinsToCredit / $coinsToDebit, 2) : 10.0;
                if ($winMoney >= 10 || $multiplier >= 5.0) {
                    $game = Game::where('game_code', $gameCode)->first();
                    LiveCommunityWin::create([
                        'user_name' => $user->name,
                        'user_avatar' => $user->avatar,
                        'game_name' => $game ? $game->name : ($gameCode ? ucwords(str_replace(['_', '-'], ' ', $gameCode)) : 'Slot Game'),
                        'game_image' => $game?->banner_url ?: '',
                        'bet_amount' => $coinsToDebit,
                        'multiplier' => $multiplier,
                        'win_amount' => $coinsToCredit,
                    ]);
                }
            }

            $providerBalanceAfter = floor(($balanceAfter / $rate) * 100) / 100;

            Log::info("✅ [Nexus transaction SUCCESS] User=[{$userCode}] Balance: {$balanceBefore} -> {$balanceAfter} SC (ReturnedToSlot: {$providerBalanceAfter} SC)");

            return response()->json([
                'status' => 1,
                'user_balance' => (float) $providerBalanceAfter,
            ]);
        });
    }

    /**
     * C. Handle Rollback/Refund
     */
    protected function handleRefund(array $payload): JsonResponse
    {
        $transactionId = $payload['transaction_id'] ?? $payload['txn_id_v2'] ?? $payload['tx_id'] ?? null;
        $refundAmount = (float) ($payload['amount'] ?? $payload['refund_amount'] ?? 0);
        $rate = (float) config('nexus.denomination_rate', 1);

        if (! $transactionId) {
            return response()->json(['status' => 0, 'msg' => 'MISSING_TRANSACTION_ID'], 400);
        }

        $existingTx = GameTransaction::where('transaction_id', (string) $transactionId)->first();
        if ($existingTx) {
            $user = User::find($existingTx->user_id);
            if ($user && $refundAmount > 0) {
                $user->increment('game_balance', $refundAmount * $rate);
            }

            $providerBalance = $user ? floor((((float) $user->game_balance) / $rate) * 100) / 100 : 0.0;

            return response()->json([
                'status' => 1,
                'user_balance' => (float) $providerBalance,
            ]);
        }

        return response()->json(['status' => 1, 'msg' => 'REFUND_RECORDED']);
    }
}

