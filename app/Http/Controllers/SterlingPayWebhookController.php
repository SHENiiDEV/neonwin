<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Services\SterlingPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class SterlingPayWebhookController extends Controller
{
    public function __invoke(Request $request, SterlingPayService $sterlingPay): JsonResponse
    {
        $secret = (string) config('sterlingpay.webhook_secret');
        if (! $sterlingPay->available() || $secret === '') {
            return response()->json(['status' => 'unavailable'], 503);
        }
        $signature = base64_encode(sha1($secret.$request->getContent().$secret, true));
        if (! hash_equals($signature, (string) $request->header('X-Signature', ''))) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $data = $request->json('data');
        $invoiceId = $data['id'] ?? null;
        $reference = $data['attributes']['reference_id'] ?? null;
        if (($data['type'] ?? null) !== 'payment-invoices' || ! is_string($invoiceId) || ! is_string($reference)) {
            return response()->json(['status' => 'invalid_request'], 422);
        }
        $deposit = Deposit::where('reference', $reference)->where('payment_provider', 'sterlingpay')->first();
        if (! $deposit) {
            return response()->json(['status' => 'unknown_reference'], 404);
        }
        try {
            $sterlingPay->refresh($deposit, $invoiceId);
        } catch (LogicException $exception) {
            Log::warning('Sterling Pay webhook invoice mismatch.', ['deposit_id' => $deposit->id, 'exception' => $exception->getMessage()]);

            return response()->json(['status' => 'invoice_mismatch'], 422);
        } catch (Throwable $exception) {
            Log::warning('Sterling Pay webhook could not be reconciled.', ['deposit_id' => $deposit->id, 'exception' => $exception->getMessage()]);

            return response()->json(['status' => 'retry'], 503);
        }

        return response()->json(['status' => 'ok', 'reference_id' => $reference]);
    }
}
