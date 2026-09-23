<?php

namespace App\Http\Controllers;

use App\Models\TippingTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TippingController extends Controller
{
    public function tip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_code' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.10', 'max:500000000'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        $sender = Auth::user();
        if (! $sender) {
            return response()->json(['status' => 'error', 'message' => 'Please log in.'], 401);
        }

        $recipientCode = trim($validated['recipient_code']);
        $recipient = User::where('user_code', $recipientCode)
            ->orWhere('name', $recipientCode)
            ->first();

        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Player not found. Please check the User Code or Name.'], 404);
        }

        if ($recipient->id === $sender->id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot send a tip to yourself.'], 422);
        }

        $amount = (float) $validated['amount'];
        if ($sender->game_balance < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient game balance to send tip.'], 422);
        }

        // 3% Platform Fee
        $fee = round($amount * 0.03, 2);
        $netAmount = round($amount - $fee, 2);

        $sender->decrement('game_balance', $amount);
        $recipient->increment('game_balance', $netAmount);

        $tipTx = TippingTransaction::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'amount_sc' => $amount,
            'fee_sc' => $fee,
            'net_amount_sc' => $netAmount,
            'note' => $validated['note'] ?? null,
        ]);

        $formattedNet = number_format($netAmount, 2);
        $formattedFee = number_format($fee, 2);

        return response()->json([
            'status' => 'success',
            'new_balance' => (float) $sender->game_balance,
            'fee' => $fee,
            'net_delivered' => $netAmount,
            'recipient_name' => $recipient->name,
            'message' => "Successfully sent {$formattedNet} SC to {$recipient->name} (fee: {$formattedFee} SC).",
        ]);
    }
}

