<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletEntry;
use App\Services\EuroMoney;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VaultController extends Controller
{
    public function deposit(Request $request): JsonResponse
    {
        return $this->transfer($request, false);
    }

    public function withdraw(Request $request): JsonResponse
    {
        return $this->transfer($request, true);
    }

    private function transfer(Request $request, bool $release): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000000'], 'pin' => ['nullable', 'string', 'size:4']]);
        abort_unless($request->user(), 401);
        $amount = EuroMoney::cents($data['amount']);
        $result = DB::transaction(function () use ($request, $release, $data, $amount): array {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($release && $user->vault_pin && (! isset($data['pin']) || ! Hash::check($data['pin'], $user->vault_pin))) {
                return ['status' => 'error', 'message' => 'Invalid 4-digit Vault PIN code.'];
            }
            if (($release ? $user->eur_vault_cents : $user->eur_balance_cents) < $amount) {
                return ['status' => 'error', 'message' => 'Insufficient EUR funds for this vault transfer.'];
            }
            $before = $user->eur_balance_cents;
            $delta = $release ? $amount : -$amount;
            $user->eur_balance_cents += $delta;
            $user->eur_vault_cents -= $delta;
            $user->save();
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'vault:'.Str::uuid(), 'kind' => $release ? 'vault_release' : 'vault_store', 'delta_cents' => $delta, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);

            return ['status' => 'success', 'game_balance' => $user->eur_balance_cents / 100, 'vault_balance' => $user->eur_vault_cents / 100, 'message' => $release ? 'EUR returned to your wallet.' : 'EUR moved to your vault.'];
        }, 3);

        return response()->json($result, $result['status'] === 'success' ? 200 : 422);
    }

    public function setPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in.'], 401);
        }

        $user->update([
            'vault_pin' => Hash::make($validated['pin']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vault PIN protection enabled successfully!',
        ]);
    }
}
