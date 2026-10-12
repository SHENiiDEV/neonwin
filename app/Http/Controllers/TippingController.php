<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletEntry;
use App\Services\EuroMoney;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TippingController extends Controller
{
    public function tip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_code' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.10', 'max:500000000'],
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

        $amount = EuroMoney::cents($validated['amount']);
        $result = DB::transaction(function () use ($sender, $recipient, $amount): array {
            $users = User::whereIn('id', [$sender->id, $recipient->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lockedSender = $users[$sender->id];
            $lockedRecipient = $users[$recipient->id];
            if ($lockedSender->eur_balance_cents < $amount) {
                return ['status' => 'error', 'message' => 'Insufficient EUR balance to send tip.'];
            }
            $fee = intdiv($amount * 3 + 50, 100);
            $net = $amount - $fee;
            $senderBefore = $lockedSender->eur_balance_cents;
            $recipientBefore = $lockedRecipient->eur_balance_cents;
            $lockedSender->eur_balance_cents -= $amount;
            $lockedRecipient->eur_balance_cents += $net;
            $lockedSender->save();
            $lockedRecipient->save();
            $id = (string) Str::uuid();
            foreach ([[$lockedSender, -$amount, $senderBefore, 'tip_sent'], [$lockedRecipient, $net, $recipientBefore, 'tip_received']] as [$user, $delta, $before, $kind]) {
                WalletEntry::create(['user_id' => $user->id, 'reference' => $kind.':'.$id, 'kind' => $kind, 'delta_cents' => $delta, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
            }

            return ['status' => 'success', 'new_balance' => $lockedSender->eur_balance_cents / 100, 'fee' => $fee / 100, 'net_delivered' => $net / 100, 'recipient_name' => $recipient->name, 'message' => 'EUR tip sent. A 3% platform fee was deducted.'];
        }, 3);

        return response()->json($result, $result['status'] === 'success' ? 200 : 422);
    }
}
