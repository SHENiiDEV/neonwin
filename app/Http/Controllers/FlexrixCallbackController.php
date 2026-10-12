<?php

namespace App\Http\Controllers;

use App\Models\OperationalAlert;
use App\Services\FlexrixService;
use App\Services\FlexrixWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class FlexrixCallbackController extends Controller
{
    public function casino(Request $request, FlexrixWallet $wallet): JsonResponse
    {
        $key = (string) config('gaming.flexrix.casino_key');
        $secret = (string) config('gaming.flexrix.casino_secret');
        $headers = ['X-Merchant-Id' => $request->header('X-Merchant-Id', ''), 'X-Timestamp' => $request->header('X-Timestamp', ''), 'X-Nonce' => $request->header('X-Nonce', '')];
        if ($key === '' || $secret === '' || ! hash_equals($key, $headers['X-Merchant-Id']) || ! ctype_digit($headers['X-Timestamp']) || abs(now()->timestamp - (int) $headers['X-Timestamp']) > 300 || $headers['X-Nonce'] === '' || ! hash_equals(hash_hmac('sha1', FlexrixService::canonical(array_merge($request->post(), $headers)), $secret), $request->header('X-Sign', ''))) {
            return response()->json(['ok' => false, 'error_code' => 'BAD_SIGNATURE'], 401);
        }

        return $this->settle($wallet, 'casino', $request->post(), $request->input('action', ''));
    }

    public function sportsbook(Request $request, string $action, FlexrixWallet $wallet): JsonResponse
    {
        $key = (string) config('gaming.flexrix.sportsbook_key');
        $secret = (string) config('gaming.flexrix.sportsbook_secret');
        if ($key === '' || $secret === '' || ! hash_equals($key, $request->header('X-Flexrix-Merchant', '')) || ! hash_equals($key, (string) $request->input('merchant_key', '')) || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $request->header('X-Flexrix-Signature', ''))) {
            return response()->json(['ok' => false, 'error_code' => 'BAD_SIGNATURE'], 401);
        }

        return $this->settle($wallet, 'sportsbook', $request->json()->all(), $action);
    }

    private function settle(FlexrixWallet $wallet, string $channel, array $body, mixed $action): JsonResponse
    {
        try {
            return response()->json($wallet->handle($channel, $body, is_string($action) ? $action : ''));
        } catch (InvalidArgumentException) {
            return response()->json(['ok' => false, 'error_code' => 'INVALID_REQUEST'], 400);
        } catch (Throwable) {
            OperationalAlert::firstOrCreate(['kind' => 'flexrix_wallet', 'reference' => hash('sha256', $channel.':'.json_encode([$body['transaction_id'] ?? null, $body['original_transaction_id'] ?? null]))], ['message' => 'Flexrix wallet callback requires retry or reconciliation. No partial wallet changes were committed.']);

            return response()->json(['ok' => false, 'error_code' => 'INTERNAL'], 503);
        }
    }
}
