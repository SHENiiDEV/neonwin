<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\OperationalAlert;
use App\Services\SterlingPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class DepositController extends Controller
{
    public const PACKAGES = ['eur_10' => 1000, 'eur_25' => 2500, 'eur_50' => 5000, 'eur_100' => 10000];

    public function deposit(Request $request, SterlingPayService $sterlingPay): JsonResponse
    {
        $data = $request->validate(['package' => ['required', Rule::in(array_keys(self::PACKAGES))]]);
        if (! $sterlingPay->available()) {
            return response()->json(['status' => 'unavailable', 'message' => 'EUR payments are not available right now. No funds have been taken.'], 503);
        }
        $user = $request->user();
        $deposit = Deposit::create([
            'reference' => (string) Str::uuid(),
            'user_id' => $user->id,
            'package' => $data['package'],
            'amount_cents' => self::PACKAGES[$data['package']],
            'currency' => 'EUR',
            'status' => 'pending',
            'payment_provider' => 'sterlingpay',
            'payment_test_mode' => (bool) config('sterlingpay.test_mode'),
        ]);
        try {
            $invoice = $sterlingPay->createInvoice($deposit, $user, strtolower($request->getHost()));
        } catch (Throwable $exception) {
            Log::warning('Sterling Pay invoice creation could not be confirmed.', ['deposit_id' => $deposit->id, 'exception' => $exception->getMessage()]);
            OperationalAlert::firstOrCreate(['kind' => 'sterlingpay_invoice', 'reference' => $deposit->reference], ['message' => 'Invoice creation was not confirmed. Check Sterling Pay before retrying this deposit.']);

            return response()->json(['status' => 'pending', 'message' => 'The payment page could not be opened. No wallet credit was added; please try again later.'], 502);
        }

        return response()->json(['status' => 'pending', 'reference' => $deposit->reference, 'hppUrl' => $invoice['hpp_url']], 201);
    }

    public function paymentReturn(Request $request): RedirectResponse
    {
        $data = $request->validate(['ref' => ['required', 'uuid']]);
        Deposit::where('reference', $data['ref'])->where('user_id', $request->user()->id)->firstOrFail();

        return redirect()->route('home', ['cashier' => 1, 'payment' => $data['ref']]);
    }

    public function status(Request $request, string $reference, SterlingPayService $sterlingPay): JsonResponse
    {
        $deposit = Deposit::where('reference', $reference)->where('user_id', $request->user()->id)->where('payment_provider', 'sterlingpay')->firstOrFail();
        if ($deposit->status === 'pending' && $deposit->payment_invoice_id !== null) {
            try {
                $deposit = $sterlingPay->refresh($deposit);
            } catch (Throwable $exception) {
                Log::warning('Sterling Pay invoice status could not be confirmed.', ['deposit_id' => $deposit->id, 'exception' => $exception->getMessage()]);
            }
        }

        return response()->json(['transactionRef' => $deposit->reference, 'status' => $deposit->status, 'amountCents' => $deposit->amount_cents, 'currency' => $deposit->currency]);
    }
}
