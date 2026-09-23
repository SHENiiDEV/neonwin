<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class VaultController extends Controller
{
    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in.'], 401);
        }

        $amount = (float) $validated['amount'];
        if ($user->game_balance < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient game balance to deposit into vault.'], 422);
        }

        $user->decrement('game_balance', $amount);
        $user->increment('vault_balance', $amount);

        $formatted = number_format($amount, 2);

        return response()->json([
            'status' => 'success',
            'game_balance' => (float) $user->game_balance,
            'vault_balance' => (float) $user->vault_balance,
            'message' => "Successfully locked {$formatted} SC inside your Cyber Vault.",
        ]);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'pin' => ['nullable', 'string', 'size:4'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in.'], 401);
        }

        // If user has a vault PIN set, verify it
        if ($user->vault_pin && (! $validated['pin'] || ! Hash::check($validated['pin'], $user->vault_pin))) {
            return response()->json(['status' => 'error', 'message' => 'Invalid 4-digit Vault PIN code.'], 422);
        }

        $amount = (float) $validated['amount'];
        if ($user->vault_balance < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient funds in vault.'], 422);
        }

        $user->decrement('vault_balance', $amount);
        $user->increment('game_balance', $amount);

        $formatted = number_format($amount, 2);

        return response()->json([
            'status' => 'success',
            'game_balance' => (float) $user->game_balance,
            'vault_balance' => (float) $user->vault_balance,
            'message' => "Successfully unlocked {$formatted} SC from your Vault to your active wallet.",
        ]);
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
