<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\OperationalAlert;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LogicException;
use RuntimeException;

class SterlingPayService
{
    public function available(): bool
    {
        return (bool) config('sterlingpay.enabled')
            && filled(config('sterlingpay.account_id'))
            && filled(config('sterlingpay.api_key'))
            && filled(config('sterlingpay.webhook_secret'))
            && FlexrixService::httpsUrl(config('sterlingpay.api_url'))
            && FlexrixService::httpsUrl(config('app.url'));
    }

    /** @return array{invoice_id: string, hpp_url: string} */
    public function createInvoice(Deposit $deposit, User $user, string $returnHost): array
    {
        if (! $this->available() || $deposit->payment_provider !== 'sterlingpay' || $deposit->currency !== 'EUR') {
            throw new RuntimeException('Sterling Pay is not configured for EUR deposits.');
        }

        $customer = array_filter([
            'reference_id' => $user->user_code,
            'email' => $user->email,
            'name' => trim($user->name.' '.($user->surname ?? '')),
            'first_name' => $user->name,
            'surname' => $user->surname,
            'phone' => $user->phone,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
        $base = rtrim((string) config('app.url'), '/');
        $returnBase = in_array($returnHost, config('sterlingpay.return_hosts'), true) ? 'https://'.$returnHost : $base;
        $returnUrl = $returnBase.'/payment/return?ref='.rawurlencode($deposit->reference);
        $payload = ['data' => [
            'type' => 'payment-invoices',
            'attributes' => [
                'reference_id' => $deposit->reference,
                'amount' => (float) EuroMoney::decimal($deposit->amount_cents),
                'currency' => 'EUR',
                'service' => config('sterlingpay.service'),
                'flow' => 'charge',
                'test_mode' => $deposit->payment_test_mode,
                'description' => 'Neonwin EUR wallet deposit',
                'customer' => $customer,
                'return_url' => $returnUrl,
                'return_urls' => ['success' => $returnUrl, 'pending' => $returnUrl, 'fail' => $returnUrl],
                'callback_url' => $base.'/api/payments/sterlingpay/webhook',
                'metadata' => ['user_id' => (string) $user->id],
            ],
        ]];
        $response = $this->client()->withBody(json_encode($payload, JSON_THROW_ON_ERROR), 'application/vnd.api+json')
            ->post(rtrim((string) config('sterlingpay.api_url'), '/').'/payment-invoices');
        if (! $response->successful()) {
            throw new RuntimeException('Sterling Pay invoice creation failed (HTTP '.$response->status().').');
        }
        $invoice = $response->json('data');
        $invoiceId = $invoice['id'] ?? null;
        $attributes = $invoice['attributes'] ?? [];
        $hppUrl = $attributes['hpp_url'] ?? null;
        if (($invoice['type'] ?? null) !== 'payment-invoices'
            || ! is_string($invoiceId)
            || ! preg_match('/^[A-Za-z0-9_-]{4,100}$/D', $invoiceId)
            || ($attributes['reference_id'] ?? null) !== $deposit->reference
            || (isset($attributes['currency']) && $attributes['currency'] !== 'EUR')
            || (isset($attributes['amount']) && EuroMoney::cents($attributes['amount']) !== $deposit->amount_cents)
            || ! $this->validHppUrl($hppUrl)) {
            throw new RuntimeException('Sterling Pay returned an invalid invoice.');
        }

        DB::transaction(function () use ($deposit, $invoiceId): void {
            $locked = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_invoice_id !== null && $locked->payment_invoice_id !== $invoiceId) {
                throw new LogicException('Deposit invoice identity changed.');
            }
            $locked->update(['payment_invoice_id' => $invoiceId]);
        });

        return ['invoice_id' => $invoiceId, 'hpp_url' => $hppUrl];
    }

    public function refresh(Deposit $deposit, ?string $expectedInvoiceId = null): Deposit
    {
        if (! $this->available() || $deposit->payment_provider !== 'sterlingpay') {
            throw new RuntimeException('Sterling Pay is not configured.');
        }
        $invoiceId = $deposit->payment_invoice_id ?? $expectedInvoiceId;
        if (! is_string($invoiceId) || ! preg_match('/^[A-Za-z0-9_-]{4,100}$/D', $invoiceId) || ($expectedInvoiceId !== null && $invoiceId !== $expectedInvoiceId)) {
            throw new LogicException('Invoice identity does not match the deposit.');
        }
        $response = $this->client()->get(rtrim((string) config('sterlingpay.api_url'), '/').'/payment-invoices/'.rawurlencode($invoiceId));
        if (! $response->successful()) {
            throw new RuntimeException('Sterling Pay invoice lookup failed (HTTP '.$response->status().').');
        }
        $invoice = $response->json('data');
        $attributes = $invoice['attributes'] ?? [];
        if (($invoice['type'] ?? null) !== 'payment-invoices' || ($invoice['id'] ?? null) !== $invoiceId || ($attributes['reference_id'] ?? null) !== $deposit->reference || ($attributes['currency'] ?? null) !== 'EUR' || ! array_key_exists('amount', $attributes) || EuroMoney::cents($attributes['amount']) !== $deposit->amount_cents || (isset($attributes['processed_amount']) && EuroMoney::cents($attributes['processed_amount']) !== $deposit->amount_cents)) {
            throw new LogicException('Sterling Pay invoice details do not match the deposit.');
        }
        $deposit = DB::transaction(function () use ($deposit, $invoiceId): Deposit {
            $locked = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_invoice_id !== null && $locked->payment_invoice_id !== $invoiceId) {
                throw new LogicException('Deposit invoice identity changed.');
            }
            if ($locked->payment_invoice_id === null) {
                $locked->update(['payment_invoice_id' => $invoiceId]);
            }

            return $locked;
        });

        $status = $attributes['status'] ?? null;
        if ($status === 'processed' && ($attributes['resolution'] ?? null) === 'ok') {
            if ($deposit->payment_test_mode || ($attributes['test_mode'] ?? false)) {
                if ($deposit->status === 'pending') {
                    $deposit->update(['status' => 'test_processed']);
                }

                return $deposit->refresh();
            }

            return app(VerifiedDepositService::class)->confirm($deposit->reference, 'sterlingpay', $invoiceId, $deposit->amount_cents, 'EUR');
        }
        if (in_array($status, ['refunded', 'partially_refunded', 'refunding', 'refund_pending'], true) && $deposit->status === 'confirmed') {
            OperationalAlert::firstOrCreate(
                ['kind' => 'sterlingpay_refund', 'reference' => $deposit->reference],
                ['message' => 'A confirmed Sterling Pay deposit was refunded or is being refunded. Review the player wallet and provider record immediately.'],
            );
        }
        if (in_array($status, ['expired', 'terminated', 'process_failed'], true) && $deposit->status === 'pending') {
            $deposit->update(['status' => 'failed']);
        }

        return $deposit->refresh();
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('sterlingpay.account_id'), (string) config('sterlingpay.api_key'))
            ->accept('application/vnd.api+json')
            ->connectTimeout(5)
            ->timeout(15)
            ->withoutRedirecting();
    }

    private function validHppUrl(mixed $url): bool
    {
        return FlexrixService::httpsUrl($url) && parse_url($url, PHP_URL_HOST) === config('sterlingpay.hpp_host');
    }
}
