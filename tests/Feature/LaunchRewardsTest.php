<?php

namespace Tests\Feature;

use App\Models\GameTransaction;
use App\Models\LaunchSetting;
use App\Models\User;
use App\Models\VipAccount;
use App\Models\VipReward;
use App\Models\WheelFundEntry;
use App\Services\LaunchRakebackService;
use App\Services\VipService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaunchRewardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 12:00:00', 'UTC'));
    }

    private function admin(int $balance = 10000): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true, 'eur_balance_cents' => $balance])->save();

        return $user;
    }

    private function funded(): void
    {
        LaunchSetting::findOrFail(1)->forceFill(['wheel_budget_cents' => 10000, 'wheel_prizes' => [['cents' => 125, 'weight' => 1]]])->save();
    }

    private function campaign(string $basis): void
    {
        LaunchSetting::findOrFail(1)->update(['rakeback_enabled' => true, 'rakeback_basis' => $basis, 'rakeback_bps' => 2000, 'rakeback_starts_at' => now()->startOfDay(), 'rakeback_ends_at' => now()->startOfDay()->addDays(2)]);
    }

    private function activity(User $user, int $bet, int $win = 0, string $round = 'round'): GameTransaction
    {
        return DB::transaction(function () use ($user, $bet, $win, $round): GameTransaction {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $transaction = GameTransaction::factory()->create(['user_id' => $user->id, 'agent_code' => 'test', 'provider_code' => 'TEST', 'game_code' => 'test', 'game_type' => 'slot', 'round_id' => $round, 'bet_amount' => $bet / 100, 'win_amount' => $win / 100]);
            app(VipService::class)->recordBet($locked, $transaction, $bet, []);
            app(LaunchRakebackService::class)->record($locked, $transaction, $bet, $win);

            return $transaction;
        });
    }

    public function test_spin_credits_the_server_prize_once_and_enforces_a_full_24_hours(): void
    {
        $this->funded();
        $user = User::factory()->create();
        $request = ['request_id' => (string) Str::uuid(), 'amount_cents' => 999999, 'prize_index' => 7];
        $this->actingAs($user)->post('/wheel/spin', $request)->assertRedirect('/wheel')->assertSessionHas('wheel_result.amount_cents', 125);
        $this->post('/wheel/spin', $request)->assertRedirect();
        $this->assertSame(125, $user->fresh()->eur_balance_cents);
        $this->assertSame(9875, LaunchSetting::find(1)->wheel_budget_cents);
        $this->assertDatabaseCount('wheel_spins', 1);
        $this->assertDatabaseCount('wallet_entries', 1);
        $this->assertDatabaseHas('wheel_fund_entries', ['delta_cents' => -125, 'balance_after_cents' => 9875]);
        $this->travel(23)->hours();
        $this->postJson('/wheel/spin', ['request_id' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('wheel');
        $this->travel(1)->hours();
        $this->post('/wheel/spin', ['request_id' => (string) Str::uuid()])->assertRedirect();
        $this->assertSame(250, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wheel_spins', 2);
    }

    public function test_underfunded_expired_and_disabled_wheels_do_not_consume_a_spin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([['wheel_budget_cents' => 0], ['wheel_budget_cents' => 10000, 'wheel_enabled' => false], ['wheel_enabled' => true, 'wheel_ends_at' => now()->subSecond()]] as $values) {
            LaunchSetting::find(1)->forceFill($values)->save();
            $this->postJson('/wheel/spin', ['request_id' => (string) Str::uuid()])->assertUnprocessable();
        }
        $this->assertDatabaseCount('wheel_spins', 0);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
    }

    public function test_funding_moves_only_existing_admin_funds_and_retries_cannot_duplicate_it(): void
    {
        $admin = $this->admin();
        $data = ['amount' => '25.50', 'request_id' => (string) Str::uuid()];
        $this->actingAs($admin)->post('/admin/launch/fund', $data)->assertSessionHasNoErrors();
        $this->post('/admin/launch/fund', $data)->assertSessionHasNoErrors();
        $this->assertSame(7450, $admin->fresh()->eur_balance_cents);
        $this->assertSame(2550, LaunchSetting::find(1)->wheel_budget_cents);
        $this->assertDatabaseCount('wheel_fund_entries', 1);
        $this->postJson('/admin/launch/fund', array_replace($data, ['amount' => '26.00']))->assertUnprocessable();
        $this->postJson('/admin/launch/fund', ['amount' => '100.00', 'request_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('wallet_entries', 1);
    }

    public function test_failed_fund_ledger_insert_rolls_back_every_wallet_change(): void
    {
        $this->funded();
        $user = User::factory()->create();
        WheelFundEntry::creating(function (): void {
            throw new \RuntimeException('Test storage failure');
        });
        Exceptions::fake();
        try {
            $this->actingAs($user)->postJson('/wheel/spin', ['request_id' => (string) Str::uuid()])->assertInternalServerError();
            $this->assertSame(0, $user->fresh()->eur_balance_cents);
            $this->assertSame(10000, LaunchSetting::find(1)->wheel_budget_cents);
            $this->assertDatabaseCount('wheel_spins', 0);
            $this->assertDatabaseCount('wallet_entries', 0);
        } finally {
            WheelFundEntry::flushEventListeners();
        }
    }

    public function test_players_cannot_edit_campaigns_or_transfer_funds_and_guest_cannot_spin(): void
    {
        $this->post('/wheel/spin')->assertRedirect();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/launch')->assertForbidden();
        foreach (['fund', 'wheel', 'rakeback', 'telegram'] as $action) {
            $this->post('/admin/launch/'.$action)->assertForbidden();
        }
        $this->assertDatabaseCount('wheel_spins', 0);
    }

    public function test_admin_configures_odds_and_schedules_both_modes_while_invalid_settings_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/launch')->assertInertia(fn ($page) => $page->component('AdminLaunch')->where('settings.rakeback_enabled', false)->where('settings.rakeback_bps', 2000));
        $prizes = array_fill(0, 8, ['cents' => '100', 'weight' => '10']);
        $this->post('/admin/launch/wheel', ['wheel_enabled' => true, 'wheel_ends_at' => '', 'wheel_prizes' => $prizes, 'wheel_budget_cents' => 99999])->assertSessionHasNoErrors();
        $this->assertSame(0, LaunchSetting::find(1)->wheel_budget_cents);
        $this->assertSame(100, LaunchSetting::find(1)->wheel_prizes[0]['cents']);
        foreach (['stakes', 'net_loss'] as $basis) {
            $this->post('/admin/launch/rakeback', ['rakeback_enabled' => true, 'rakeback_basis' => $basis, 'percent' => '20.00', 'starts_on' => '2026-10-13', 'ends_on' => '2026-10-31'])->assertSessionHasNoErrors();
            $this->assertSame($basis, LaunchSetting::find(1)->rakeback_basis);
        }
        $this->postJson('/admin/launch/rakeback', ['rakeback_enabled' => true, 'rakeback_basis' => 'net_loss', 'percent' => '101', 'starts_on' => '2026-10-12', 'ends_on' => '2026-10-01'])->assertUnprocessable();
        $prizes[0]['weight'] = 0;
        $this->postJson('/admin/launch/wheel', ['wheel_enabled' => true, 'wheel_prizes' => $prizes])->assertUnprocessable();
    }

    public function test_stake_campaign_replaces_daily_rate_and_snapshots_terms_until_midnight(): void
    {
        $this->campaign('stakes');
        $user = User::factory()->create();
        $first = $this->activity($user, 10000, 5000);
        $reward = VipReward::where('key', 'launch:daily:2026-10-12')->firstOrFail();
        $this->assertSame(2000, $reward->amount_cents);
        $this->assertDatabaseMissing('vip_rewards', ['key' => 'rakeback:daily:2026-10-12']);
        LaunchSetting::find(1)->update(['rakeback_bps' => 500, 'rakeback_basis' => 'net_loss']);
        $this->activity($user, 1000, 0, 'second');
        $this->assertSame(2200, $reward->fresh()->amount_cents);
        $this->actingAs($user)->postJson('/vip/rewards/'.$reward->id.'/claim')->assertUnprocessable();
        $this->travel(1)->days();
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $this->assertSame(2200, $user->fresh()->eur_balance_cents);
        $locked = $user->fresh();
        DB::transaction(fn () => app(VipService::class)->reverseBet($locked, $first->id, 5000));
        $this->assertSame(1200, $user->fresh()->eur_balance_cents);
        $this->assertSame(1200, $reward->fresh()->amount_cents);
    }

    public function test_net_loss_handles_profit_late_corrections_and_reversal_after_claim_without_double_credit(): void
    {
        $this->campaign('net_loss');
        $user = User::factory()->create();
        $bet = $this->activity($user, 10000);
        $win = $this->activity($user, 0, 6000);
        $reward = VipReward::where('key', 'launch:daily:2026-10-12')->firstOrFail();
        $this->assertSame(800, $reward->amount_cents);
        $this->travel(1)->days();
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $this->assertSame(800, $user->fresh()->eur_balance_cents);
        DB::transaction(fn () => app(LaunchRakebackService::class)->reverseTransaction($user->fresh(), $win->id));
        $this->assertSame(2000, $reward->fresh()->amount_cents);
        $this->assertSame(1200, $reward->fresh()->claimable_cents);
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $this->assertSame(2000, $user->fresh()->eur_balance_cents);
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        DB::transaction(fn () => app(VipService::class)->reverseBet($user->fresh(), $bet->id));
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertSame(0, $reward->fresh()->amount_cents);
        $this->assertSame(0, VipAccount::first()->reward_debt_cents);
    }

    public function test_campaign_expires_to_standard_vip_and_net_profit_earns_zero(): void
    {
        $this->campaign('net_loss');
        $user = User::factory()->create();
        $this->activity($user, 10000, 15000);
        $this->assertSame(0, VipReward::where('key', 'launch:daily:2026-10-12')->first()->amount_cents);
        $this->travel(2)->days();
        $this->activity($user, 10000);
        $this->assertDatabaseMissing('vip_rewards', ['key' => 'launch:daily:2026-10-14']);
        $this->assertDatabaseHas('vip_rewards', ['key' => 'rakeback:daily:2026-10-14', 'amount_cents' => 12]);
    }

    public function test_unrecoverable_refund_debt_offsets_the_next_launch_reward(): void
    {
        $this->campaign('stakes');
        $user = User::factory()->create();
        $bet = $this->activity($user, 10000);
        $reward = VipReward::where('key', 'launch:daily:2026-10-12')->firstOrFail();
        $this->travel(1)->days();
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $user->fresh()->forceFill(['eur_balance_cents' => 0])->save();
        DB::transaction(fn () => app(VipService::class)->reverseBet($user->fresh(), $bet->id));
        $this->assertSame(2000, VipAccount::first()->reward_debt_cents);
        $this->activity($user, 15000, 0, 'next-day');
        $next = VipReward::where('key', 'launch:daily:2026-10-13')->firstOrFail();
        $this->travel(1)->days();
        $this->post('/vip/rewards/'.$next->id.'/claim')->assertSessionHasNoErrors();
        $this->assertSame(1000, $user->fresh()->eur_balance_cents);
        $this->assertSame(0, VipAccount::first()->reward_debt_cents);
    }

    public function test_correction_that_removes_an_unpaid_difference_does_not_offer_a_zero_claim(): void
    {
        $this->campaign('net_loss');
        $user = User::factory()->create();
        $bet = $this->activity($user, 10000);
        $win = $this->activity($user, 0, 6000);
        $reward = VipReward::where('key', 'launch:daily:2026-10-12')->firstOrFail();
        $this->travel(1)->days();
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        DB::transaction(fn () => app(LaunchRakebackService::class)->reverseTransaction($user->fresh(), $win->id));
        $this->assertSame(1200, $reward->fresh()->claimable_cents);
        DB::transaction(fn () => app(VipService::class)->reverseBet($user->fresh(), $bet->id, 6000));
        $this->assertSame(800, $reward->fresh()->amount_cents);
        $this->assertSame('claimed', $reward->fresh()->status);
        $this->assertSame(0, $reward->fresh()->claimable_cents);
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHasNoErrors();
        $this->assertSame(800, $user->fresh()->eur_balance_cents);
    }
}
