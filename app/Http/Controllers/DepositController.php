<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public const PACKAGES = ['eur_10' => 1000, 'eur_25' => 2500, 'eur_50' => 5000, 'eur_100' => 10000];

    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate(['package' => ['required', Rule::in(array_keys(self::PACKAGES))]]);
        $deposit = Deposit::create(['reference' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'package' => $data['package'], 'amount_cents' => self::PACKAGES[$data['package']], 'status' => 'unavailable']);

        return response()->json(['status' => 'unavailable', 'reference' => $deposit->reference, 'message' => 'Payments are not available yet. Your request was recorded; your wallet has not been credited.'], 503);
    }
}
