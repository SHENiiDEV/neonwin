<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as OutboundRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SterlingPayTest extends TestCase
{
    use RefreshDatabase;

    private string $invoiceStatus = 'processed';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://neonwin.co.uk',
            'sterlingpay.enabled' => true,
            'sterlingpay.account_id' => 'account-test',
            'sterlingpay.api_key' => 'api-key-test',
            'sterlingpay.webhook_secret' => 'webhook-secret-test',
            'sterlingpay.test_mode' => false,
        ]);
        Http::preventStrayRequests();
        Queue::fake();
        Promotion::where('kind', 'first_deposit')->update(['enabled' => false]);
    }

    private function fakeInvoice(string $status = 'processed', string $currency = 'EUR', float $amount = 25.00, string $resolution = 'ok'): void
    {
        $this->invoiceStatus = $status;
        Http::fake(function (OutboundRequest $request) use ($currency, $amount, $resolution) {
            if ($request->method() === 'POST') {
                $reference = json_decode($request->body(), true)['data']['attributes']['reference_id'];

                return Http::response(['data' => ['type' => 'payment-invoices', 'id' => 'cpi_test123', 'attributes' => [
                    'reference_id' => $reference, 'status' => 'pending', 'amount' => 25, 'currency' => 'EUR',
                    'hpp_url' => 'https://pay.sterling-pay.com/process/cpi_test123',
                ]]], 201);
            }

            return Http::response(['data' => ['type' => 'payment-invoices', 'id' => 'cpi_test123', 'attributes' => [
                'reference_id' => Deposit::firstOrFail()->reference, 'status' => $this->invoiceStatus, 'resolution' => $resolution,
                'amount' => $amount, 'currency' => $currency, 'test_mode' => false,
            ]]]);
        });
    }

    private function signedWebhook(Deposit $deposit, string $invoiceId = 'cpi_test123', bool $validSignature = true): TestResponse
    {
        $body = json_encode(['data' => ['type' => 'payment-invoices', 'id' => $invoiceId, 'attributes' => ['reference_id' => $deposit->reference]]], JSON_THROW_ON_ERROR);
        $signature = base64_encode(sha1('webhook-secret-test'.$body.'webhook-secret-test', true));

        return $this->call('POST', '/api/payments/sterlingpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $validSignature ? $signature : 'invalid',
        ], $body);
    }

    public function test_creates_a_eur_invoice_with_basic_auth_and_only_returns_a_trusted_hpp_url(): void
    {
        $this->fakeInvoice();
        $user = User::factory()->create(['user_code' => 'NW-PLAYER123']);
        $response = $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25']);

        $response->assertCreated()->assertJsonPath('hppUrl', 'https://pay.sterling-pay.com/process/cpi_test123');
        $deposit = Deposit::firstOrFail();
        $this->assertSame('pending', $deposit->status);
        $this->assertSame('cpi_test123', $deposit->payment_invoice_id);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        Http::assertSent(function (OutboundRequest $request) use ($deposit): bool {
            $attributes = json_decode($request->body(), true)['data']['attributes'];

            return $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('account-test:api-key-test'))
                && $attributes['reference_id'] === $deposit->reference
                && (float) $attributes['amount'] === 25.0
                && $attributes['currency'] === 'EUR'
                && $attributes['callback_url'] === 'https://neonwin.co.uk/api/payments/sterlingpay/webhook';
        });
    }

    public function test_valid_webhook_credits_once_and_status_is_private_to_the_player(): void
    {
        $this->fakeInvoice();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertCreated();
        $deposit = Deposit::firstOrFail();
        $this->signedWebhook($deposit)->assertOk()->assertJsonPath('status', 'ok');
        $this->signedWebhook($deposit)->assertOk();

        $this->assertSame(2500, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 1);
        $this->assertSame('confirmed', $deposit->fresh()->status);
        $this->getJson('/api/payments/orders/'.$deposit->reference.'/status')->assertOk()->assertJsonPath('status', 'confirmed');
        $this->actingAs(User::factory()->create())->getJson('/api/payments/orders/'.$deposit->reference.'/status')->assertNotFound();
    }

    public function test_invalid_signature_and_mismatched_provider_amount_never_credit(): void
    {
        $this->fakeInvoice(amount: 50.00);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertCreated();
        $deposit = Deposit::firstOrFail();

        $this->signedWebhook($deposit, validSignature: false)->assertUnauthorized();
        $this->signedWebhook($deposit)->assertUnprocessable();
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_untrusted_hosted_payment_url_is_never_returned_to_the_player(): void
    {
        Http::fake(function (OutboundRequest $request) {
            $reference = json_decode($request->body(), true)['data']['attributes']['reference_id'];

            return Http::response(['data' => ['type' => 'payment-invoices', 'id' => 'cpi_test123', 'attributes' => [
                'reference_id' => $reference, 'amount' => 25, 'currency' => 'EUR',
                'hpp_url' => 'https://payment-attacker.example/process/cpi_test123',
            ]]], 201);
        });
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])
            ->assertStatus(502)
            ->assertJsonMissing(['hppUrl' => 'https://payment-attacker.example/process/cpi_test123']);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_test_mode_checkout_never_credits_real_eur(): void
    {
        config(['sterlingpay.test_mode' => true]);
        $this->fakeInvoice();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertCreated();
        $deposit = Deposit::firstOrFail();

        $this->signedWebhook($deposit)->assertOk();
        $this->assertSame('test_processed', $deposit->fresh()->status);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_return_parameters_never_credit_and_pending_status_is_checked_server_side(): void
    {
        $this->fakeInvoice(status: 'processing');
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertCreated();
        $deposit = Deposit::firstOrFail();

        $this->get('/payment/return?ref='.$deposit->reference.'&status=processed')->assertRedirect('/?cashier=1&payment='.$deposit->reference);
        $this->getJson('/api/payments/orders/'.$deposit->reference.'/status')->assertOk()->assertJsonPath('status', 'pending');
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
    }

    public function test_refund_after_confirmation_creates_an_operational_alert(): void
    {
        $this->fakeInvoice();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertCreated();
        $deposit = Deposit::firstOrFail();
        $this->signedWebhook($deposit)->assertOk();
        $this->invoiceStatus = 'refunded';
        $this->signedWebhook($deposit)->assertOk();

        $this->assertDatabaseHas('operational_alerts', ['kind' => 'sterlingpay_refund', 'reference' => $deposit->reference, 'status' => 'open']);
    }
}
