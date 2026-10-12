<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\Provider;
use App\Models\User;
use App\Services\NexusGgrService;
use App\Services\PromotionService;
use App\Services\VerifiedDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private function registration(string $code = ''): array
    {
        return ['name' => 'Player', 'surname' => 'Example', 'email' => 'player@example.test', 'password' => 'test-password', 'phone' => '+37120000000', 'date_of_birth' => '1990-01-01', 'street' => 'Example street', 'city' => 'Riga', 'country' => 'Latvia', 'postcode' => 'LV-1000', 'terms' => true, 'promo_code' => $code];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function pendingDeposit(User $user, int $amount = 2500): Deposit
    {
        return Deposit::factory()->create(['user_id' => $user->id, 'status' => 'pending', 'payment_provider' => 'test-provider', 'amount_cents' => $amount]);
    }

    public function test_first_verified_deposit_receives_twenty_percent_once_and_later_deposits_do_not(): void
    {
        $user = User::factory()->create(['game_balance' => 55]);
        $first = $this->pendingDeposit($user);
        $service = app(VerifiedDepositService::class);
        $service->confirm($first->reference, 'test-provider', 'first', 2500, 'EUR');
        $service->confirm($first->reference, 'test-provider', 'first', 2500, 'EUR');
        $this->assertSame(3000, $user->fresh()->eur_balance_cents);
        $this->assertSame('55.00', $user->fresh()->game_balance);
        $this->assertDatabaseCount('wallet_entries', 2);
        $this->assertDatabaseHas('promotion_redemptions', ['user_id' => $user->id, 'bonus_cents' => 500, 'status' => 'awarded']);
        $second = $this->pendingDeposit($user);
        $service->confirm($second->reference, 'test-provider', 'second', 2500, 'EUR');
        $this->assertSame(5500, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('promotion_redemptions', 1);
    }

    public function test_registration_promo_keeps_reserved_percentage_after_edit_and_does_not_stack(): void
    {
        Mail::fake();
        $promotion = Promotion::factory()->create(['code' => 'WELCOME50', 'percent_bps' => 5000, 'max_bonus_cents' => 1000]);
        $this->post('/register', $this->registration(' welcome50 '))->assertSessionHasNoErrors();
        $player = User::where('email', 'player@example.test')->firstOrFail();
        $this->assertSame(0, $player->eur_balance_cents);
        $promotion->update(['percent_bps' => 1000, 'enabled' => false]);
        $deposit = $this->pendingDeposit($player);
        app(VerifiedDepositService::class)->confirm($deposit->reference, 'test-provider', 'first', 2500, 'EUR');
        $this->assertSame(3500, $player->fresh()->eur_balance_cents);
        $this->assertDatabaseHas('promotion_redemptions', ['promotion_id' => $promotion->id, 'bonus_cents' => 1000, 'status' => 'awarded']);
        $this->assertDatabaseHas('promotion_redemptions', ['promotion_id' => Promotion::where('kind', 'first_deposit')->value('id'), 'bonus_cents' => 0, 'status' => 'not_applied']);
        $this->assertDatabaseCount('wallet_entries', 2);
    }

    public function test_unavailable_or_exhausted_code_does_not_create_account_or_reservation(): void
    {
        Mail::fake();
        $promotion = Promotion::factory()->create(['code' => 'CLOSED', 'ends_at' => now()->subMinute()]);
        $this->post('/register', $this->registration('CLOSED'))->assertSessionHasErrors('promo_code');
        $this->assertDatabaseMissing('users', ['email' => 'player@example.test']);
        $promotion->update(['ends_at' => null, 'redemption_limit' => 1]);
        PromotionRedemption::factory()->create(['promotion_id' => $promotion->id]);
        $this->post('/register', $this->registration('CLOSED'))->assertSessionHasErrors('promo_code');
        $this->assertDatabaseMissing('users', ['email' => 'player@example.test']);
        $this->assertDatabaseCount('promotion_redemptions', 1);
        Mail::assertNothingSent();
    }

    public function test_admin_can_configure_offer_but_players_and_invalid_percentages_are_rejected(): void
    {
        $offer = Promotion::where('kind', 'first_deposit')->firstOrFail();
        $data = ['name' => 'Welcome offer', 'kind' => 'first_deposit', 'enabled' => true, 'percent' => '25.50', 'min_deposit' => '10.00', 'max_bonus' => '100', 'terms' => '25.5% on the first confirmed deposit.'];
        $this->get('/admin/promotions')->assertRedirect();
        $this->actingAs(User::factory()->create())->post('/admin/promotions/'.$offer->id, $data)->assertForbidden();
        $this->actingAs($this->admin())->post('/admin/promotions/'.$offer->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(2550, $offer->fresh()->percent_bps);
        $this->assertSame(10000, $offer->fresh()->max_bonus_cents);
        $this->assertDatabaseHas('admin_audits', ['action' => 'promotion_saved', 'target' => (string) $offer->id]);
        $this->post('/admin/promotions/'.$offer->id, array_merge($data, ['percent' => '-1']))->assertSessionHasErrors('percent');
        $this->assertSame(2550, $offer->fresh()->percent_bps);
        $this->get('/admin/promotions')->assertOk();
    }

    public function test_minimum_applies_only_to_first_deposit_and_unavailable_requests_never_award(): void
    {
        Promotion::where('kind', 'first_deposit')->update(['min_deposit_cents' => 3000]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertStatus(503);
        $this->assertDatabaseCount('promotion_redemptions', 0);
        $deposit = $this->pendingDeposit($user);
        app(VerifiedDepositService::class)->confirm($deposit->reference, 'test-provider', 'first', 2500, 'EUR');
        $this->assertSame(2500, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseHas('promotion_redemptions', ['status' => 'not_applied', 'bonus_cents' => 0]);
        $second = $this->pendingDeposit($user, 5000);
        app(VerifiedDepositService::class)->confirm($second->reference, 'test-provider', 'second', 5000, 'EUR');
        $this->assertSame(7500, $user->fresh()->eur_balance_cents);
    }

    public function test_freespins_registration_delivers_one_durable_tour_without_crediting_cash(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777, 'msg' => 'SUCCESS'])]);
        config(['nexus.eur_agent_confirmed' => true, 'nexus.rewards_enabled' => true, 'nexus.agent_code' => 'test-agent', 'nexus.agent_token' => 'test-token']);
        $admin = $this->admin();
        Provider::factory()->create(['code' => 'PRAGMATIC', 'status' => true]);
        Game::factory()->create(['provider_code' => 'PRAGMATIC', 'game_code' => 'promo-game', 'status' => true, 'bet_levels' => ['1' => 0.2]]);
        Promotion::factory()->create(['code' => 'SPINS10', 'kind' => 'free_spins', 'created_by' => $admin->id, 'reward' => ['game_code' => 'promo-game', 'bet_level' => 1, 'spin_count' => 10, 'amount' => '2.00', 'expiry_hours' => 24]]);
        $this->post('/register', $this->registration('SPINS10'))->assertSessionHasNoErrors();
        $redemption = PromotionRedemption::firstOrFail();
        $this->assertSame('issued', $redemption->status);
        $this->assertSame(0, $redemption->user->eur_balance_cents);
        $this->actingAs($admin)->post('/admin/promotion-redemptions/'.$redemption->id.'/issue')->assertSessionHas('success');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['method'] === 'tour_create' && $request['tour_id'] === $redemption->request_id && $request['spin_count'] === 10);
        $this->assertDatabaseCount('nexus_operations', 1);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_reserved_freespins_wait_for_capability_and_expired_entitlements_are_not_sent(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777])]);
        config(['nexus.eur_agent_confirmed' => true, 'nexus.rewards_enabled' => false]);
        $this->freezeTime();
        $redemption = PromotionRedemption::factory()->create(['snapshot' => ['name' => 'FreeSpins', 'kind' => 'free_spins', 'expiration_time' => now()->addHour()->toISOString(), 'reward' => ['spin_count' => 10]]]);
        $service = app(PromotionService::class);
        $service->issueFreeSpins($redemption, $this->admin());
        $this->assertSame('pending', $redemption->fresh()->status);
        $this->assertStringContainsString('reserved', $redemption->fresh()->message);
        $this->travel(2)->hours();
        config(['nexus.rewards_enabled' => true]);
        $service->issueFreeSpins($redemption, $this->admin());
        $this->assertSame('expired', $redemption->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_unknown_freespins_delivery_keeps_request_id_without_a_second_remote_create(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.nexusggr.eu' => Http::failedConnection()]);
        config(['nexus.eur_agent_confirmed' => true, 'nexus.rewards_enabled' => true, 'nexus.agent_code' => 'test-agent', 'nexus.agent_token' => 'test-token']);
        $provider = Provider::factory()->create(['code' => 'PRAGMATIC']);
        Game::factory()->create(['provider_code' => $provider->code, 'game_code' => 'promo-game', 'status' => true, 'bet_levels' => ['1' => 0.2]]);
        $redemption = PromotionRedemption::factory()->create(['snapshot' => ['name' => 'FreeSpins', 'kind' => 'free_spins', 'expiration_time' => now()->addHour()->toISOString(), 'reward' => ['game_code' => 'promo-game', 'spin_count' => 10, 'bet_level' => 1, 'amount' => '2.00']]]);
        $service = app(PromotionService::class);
        $admin = $this->admin();
        $service->issueFreeSpins($redemption, $admin);
        $service->issueFreeSpins($redemption->fresh(), $admin);
        $this->assertSame('unknown', $redemption->fresh()->status);
        $this->assertDatabaseHas('nexus_operations', ['external_id' => $redemption->request_id, 'status' => 'unknown']);
        Http::assertSentCount(1);
        $this->assertSame(0, $redemption->user->eur_balance_cents);
    }

    public function test_nexus_ip_restriction_returns_a_safe_actionable_error(): void
    {
        Http::preventStrayRequests();
        config(['nexus.agent_code' => 'test-agent', 'nexus.agent_token' => 'secret-token']);
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 0, 'code' => 'INVALID_IP', 'message' => 'secret-token'], 403)]);
        $result = app(NexusGgrService::class)->getMoneyInfo();
        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('not whitelisted', $result['msg']);
        $this->assertStringNotContainsString('secret-token', $result['msg']);
        $this->assertFalse($result['unknown']);
        Http::assertSentCount(1);
    }

    public function test_admin_can_create_a_normalized_registration_code_with_dates_and_duplicate_codes_are_rejected(): void
    {
        $this->freezeTime();
        $data = ['name' => 'Welcome thirty', 'code' => ' welcome30 ', 'kind' => 'deposit_bonus', 'enabled' => true, 'percent' => 30, 'min_deposit' => 10, 'max_bonus' => 50, 'starts_at' => now()->subHour()->toISOString(), 'ends_at' => now()->addDay()->toISOString(), 'redemption_limit' => 100, 'terms' => '30% on your first confirmed deposit of at least €10, up to €50.'];
        $this->actingAs($this->admin())->post('/admin/promotions', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('promotions', ['code' => 'WELCOME30', 'percent_bps' => 3000, 'min_deposit_cents' => 1000, 'max_bonus_cents' => 5000, 'redemption_limit' => 100]);
        $this->post('/admin/promotions', $data)->assertSessionHasErrors('code');
        $this->assertDatabaseCount('promotions', 2);
        $this->post('/admin/promotions', array_merge($data, ['code' => 'OTHER', 'ends_at' => now()->subDay()->toISOString()]))->assertSessionHasErrors('ends_at');
    }
}
