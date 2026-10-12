<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\User;
use App\Models\VipAccount;
use App\Models\VipBet;
use App\Models\VipQuest;
use App\Models\VipReward;
use App\Models\VipTier;
use App\Models\WalletEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

class VipClubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexus.agent_code' => 'test-agent', 'nexus.agent_secret' => 'test-secret', 'nexus.eur_agent_confirmed' => true]);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 12:00:00', 'UTC'));
    }

    private function bet(User $user, string $id, string $amount = '1.00', string $round = '100', array $extra = []): array
    {
        return ['agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'method' => 'transaction', 'user_code' => $user->user_code, 'game_type' => 'slot', 'slot' => array_merge(['provider_code' => 'PRAGMATIC', 'game_code' => 'vs20olympgate', 'round_id' => $round, 'txn_id' => 'legacy-'.$id, 'txn_id_v2' => $id, 'txn_type' => 'debit', 'bet_money' => $amount], $extra)];
    }

    public function test_paid_rounds_and_wagers_are_recorded_once_and_free_events_are_excluded(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $quest = VipQuest::factory()->create(['kind' => 'spins', 'target' => 2]);
        $payload = $this->bet($user, 'one');
        $this->postJson('/gold_api', $payload)->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $payload)->assertJsonPath('msg', 'TRANSACTION_ALREADY_PROCESSED');
        $this->postJson('/gold_api', $this->bet($user, 'two'))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'free', '0.00', '101', ['tour_id' => 'tour']))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'bonus', '1.00', '102', ['bonus_id' => 'bonus']))->assertJsonPath('status', 1);

        $this->assertSame(200, VipAccount::first()->wager_cents);
        $this->assertSame(2, VipBet::count());
        $this->assertSame(1, VipBet::whereNotNull('paid_round_key')->count());
        $this->assertDatabaseHas('vip_rewards', ['key' => 'quest:'.$quest->id.':2026-10-12', 'progress' => 1, 'status' => 'active']);
    }

    public function test_level_jump_creates_each_unlock_reward_and_claim_is_exactly_once(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 100000]);
        $this->postJson('/gold_api', $this->bet($user, 'large', '600.00'))->assertJsonPath('status', 1);
        $reward = VipReward::where('user_id', $user->id)->where('key', 'level:2')->firstOrFail();
        $this->assertSame(3, VipAccount::first()->level);
        $this->assertDatabaseHas('vip_rewards', ['key' => 'level:3', 'amount_cents' => 300]);
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim', ['amount_cents' => 1000000])->assertSessionHas('success');
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHas('success');

        $this->assertSame(40100, $user->fresh()->eur_balance_cents);
        $this->assertSame(1, WalletEntry::where('reference', 'vip:'.$reward->id)->count());
    }

    public function test_rakeback_snapshots_rates_and_only_closed_periods_can_be_claimed(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 100000]);
        $this->postJson('/gold_api', $this->bet($user, 'first', '100.00'))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'second', '100.00', '101'))->assertJsonPath('status', 1);
        $daily = VipReward::where('key', 'rakeback:daily:2026-10-12')->firstOrFail();
        $weekly = VipReward::where('key', 'rakeback:weekly:2026-10-12')->firstOrFail();
        $monthly = VipReward::where('key', 'rakeback:monthly:2026-10-01')->firstOrFail();
        VipTier::query()->update(['daily_bps' => 100, 'weekly_bps' => 100, 'monthly_bps' => 100]);
        $this->actingAs($user)->postJson('/vip/rewards/'.$daily->id.'/claim')->assertUnprocessable()->assertJsonValidationErrors('reward');
        $this->assertSame(22, $daily->amount_cents);
        $this->assertSame(11, $weekly->amount_cents);
        $this->assertSame(11, $monthly->amount_cents);

        $this->travelTo(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC'));
        $this->post('/vip/rewards/'.$daily->id.'/claim')->assertSessionHas('success');
        $this->postJson('/vip/rewards/'.$weekly->id.'/claim')->assertUnprocessable();
        $this->travelTo(CarbonImmutable::parse('2026-10-19 00:00:00', 'UTC'));
        $this->post('/vip/rewards/'.$weekly->id.'/claim')->assertSessionHas('success');
        $this->postJson('/vip/rewards/'.$monthly->id.'/claim')->assertUnprocessable();
        $this->travelTo(CarbonImmutable::parse('2026-11-01 00:00:00', 'UTC'));
        $this->post('/vip/rewards/'.$monthly->id.'/claim')->assertSessionHas('success');
        $this->assertSame(80044, $user->fresh()->eur_balance_cents);
    }

    public function test_checkin_is_once_per_utc_day_with_seven_day_cycle_and_gap_reset(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (range(0, 6) as $day) {
            $this->travelTo(CarbonImmutable::parse('2026-10-12 23:59:00', 'UTC')->addDays($day));
            $this->post('/vip/check-in')->assertRedirect()->assertSessionHas('success');
            $this->post('/vip/check-in')->assertRedirect()->assertSessionHas('success');
        }
        $this->assertSame(280, $user->fresh()->eur_balance_cents);
        $this->assertSame(7, VipAccount::first()->streak);
        $this->assertSame(7, WalletEntry::where('kind', 'vip_checkin')->count());
        $this->travelTo(CarbonImmutable::parse('2026-10-19 00:00:00', 'UTC'));
        $this->post('/vip/check-in')->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, VipAccount::first()->streak);
        $this->travelTo(CarbonImmutable::parse('2026-10-21 00:00:00', 'UTC'));
        $this->post('/vip/check-in')->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, VipAccount::first()->streak);
        $this->assertSame(300, $user->fresh()->eur_balance_cents);
    }

    public function test_quest_progress_keeps_original_terms_after_admin_edit(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 1000]);
        $quest = VipQuest::factory()->create(['target' => 100, 'reward_cents' => 10]);
        $this->postJson('/gold_api', $this->bet($user, 'one', '0.50'))->assertJsonPath('status', 1);
        $quest->update(['target' => 10000, 'reward_cents' => 999]);
        $this->postJson('/gold_api', $this->bet($user, 'two', '0.50', '101'))->assertJsonPath('status', 1);
        $reward = VipReward::where('key', 'quest:'.$quest->id.':2026-10-12')->firstOrFail();
        $this->assertSame('available', $reward->status);
        $this->assertSame(10, $reward->amount_cents);
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim')->assertSessionHas('success');
        $this->assertSame(910, $user->fresh()->eur_balance_cents);
    }

    public function test_guests_and_other_users_cannot_claim_rewards(): void
    {
        $reward = VipReward::factory()->create();
        $this->post('/vip/check-in')->assertRedirect();
        $this->post('/vip/rewards/'.$reward->id.'/claim')->assertRedirect();
        $this->actingAs(User::factory()->create())->post('/vip/rewards/'.$reward->id.'/claim')->assertNotFound();
        $this->assertDatabaseCount('wallet_entries', 0);
        $this->get('/admin/vip')->assertForbidden();
    }

    public function test_admin_can_configure_quests_and_rejects_unverifiable_bonus_buys(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin/vip')->assertInertia(fn ($page) => $page->component('AdminVip')->has('tiers', 10));
        $data = ['name' => 'Daily test', 'kind' => 'wager', 'target' => '50.00', 'min_bet' => '0.20', 'reward' => '0.50', 'enabled' => true];
        $this->post('/admin/vip/quests', $data)->assertSessionHas('success');
        $this->assertDatabaseHas('vip_quests', ['name' => 'Daily test', 'target' => 5000, 'reward_cents' => 50]);
        $this->assertDatabaseHas('admin_audits', ['action' => 'vip_quest_saved']);
        $this->postJson('/admin/vip/quests', array_replace($data, ['kind' => 'bonus_buy']))->assertUnprocessable()->assertJsonValidationErrors('kind');
        $this->postJson('/admin/vip/quests', array_replace($data, ['game_code' => 'unknown', 'provider_code' => 'PRAGMATIC']))->assertUnprocessable()->assertJsonValidationErrors('game_code');
    }

    public function test_admin_tier_settings_validate_thresholds_and_total_rakeback(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $rows = VipTier::orderBy('level')->get()->map(fn ($tier) => ['level' => $tier->level, 'name' => $tier->name, 'threshold' => (string) ($tier->threshold_cents / 100), 'bonus' => (string) ($tier->bonus_cents / 100), 'checkin' => '0.10', 'daily_percent' => '0.25', 'weekly_percent' => '0.25', 'monthly_percent' => '0.25'])->all();
        $this->actingAs($admin)->post('/admin/vip/tiers', ['tiers' => $rows])->assertSessionHas('success');
        $this->assertDatabaseHas('vip_tiers', ['level' => 1, 'daily_bps' => 25]);
        $this->assertSame(1, AdminAudit::where('action', 'vip_tiers_saved')->count());
        $rows[1]['threshold'] = '0';
        $this->postJson('/admin/vip/tiers', ['tiers' => $rows])->assertUnprocessable()->assertJsonValidationErrors('tiers');
        $rows[1]['threshold'] = '100';
        $rows[1]['daily_percent'] = '3';
        $rows[1]['weekly_percent'] = '3';
        $this->postJson('/admin/vip/tiers', ['tiers' => $rows])->assertUnprocessable()->assertJsonValidationErrors('tiers');
    }

    public function test_fractional_rakeback_accumulates_before_rounding_and_zero_rewards_cannot_be_claimed(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 1000]);
        VipTier::where('level', 1)->update(['daily_bps' => 100]);
        $this->postJson('/gold_api', $this->bet($user, 'small-one', '0.50'))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'small-two', '0.50', '101'))->assertJsonPath('status', 1);
        $daily = VipReward::where('key', 'rakeback:daily:2026-10-12')->firstOrFail();
        $weekly = VipReward::where('key', 'rakeback:weekly:2026-10-12')->firstOrFail();
        $this->assertSame(1, $daily->amount_cents);
        $this->assertSame(0, $weekly->amount_cents);
        $this->travelTo(CarbonImmutable::parse('2026-10-19 00:00:00', 'UTC'));
        $this->actingAs($user)->postJson('/vip/rewards/'.$weekly->id.'/claim')->assertUnprocessable()->assertJsonValidationErrors('reward');
        $this->assertSame(900, $user->fresh()->eur_balance_cents);
    }

    public function test_failed_ledger_write_rolls_back_reward_and_wallet_together(): void
    {
        $user = User::factory()->create();
        $reward = VipReward::factory()->create(['user_id' => $user->id, 'amount_cents' => 100]);
        WalletEntry::factory()->create(['user_id' => $user->id, 'reference' => 'vip:'.$reward->id, 'delta_cents' => 0, 'balance_before_cents' => 0, 'balance_after_cents' => 0]);
        Exceptions::fake();

        $this->actingAs($user)->postJson('/vip/rewards/'.$reward->id.'/claim')->assertInternalServerError();

        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertSame('available', $reward->fresh()->status);
        $this->assertDatabaseCount('wallet_entries', 1);
        Exceptions::assertReported(UniqueConstraintViolationException::class);
    }

    public function test_game_specific_quests_require_matching_game_minimum_bet_and_current_day(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 1000]);
        $quest = VipQuest::factory()->create(['kind' => 'spins', 'provider_code' => 'PRAGMATIC', 'game_code' => 'vs20olympgate', 'target' => 2, 'min_bet_cents' => 50]);
        $this->postJson('/gold_api', $this->bet($user, 'wrong', '1.00', '100', ['game_code' => 'different']))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'low', '0.10', '101'))->assertJsonPath('status', 1);
        $this->postJson('/gold_api', $this->bet($user, 'match', '0.50', '102'))->assertJsonPath('status', 1);
        $this->assertDatabaseHas('vip_rewards', ['key' => 'quest:'.$quest->id.':2026-10-12', 'progress' => 1, 'status' => 'active']);
        $this->travelTo(CarbonImmutable::parse('2026-10-13 00:00:00', 'UTC'));
        $this->postJson('/gold_api', $this->bet($user, 'new-day', '0.50', '103'))->assertJsonPath('status', 1);
        $this->assertDatabaseHas('vip_rewards', ['key' => 'quest:'.$quest->id.':2026-10-13', 'progress' => 1, 'status' => 'active']);
        $this->actingAs($user)->get('/vip')->assertInertia(fn ($page) => $page->component('VipClub')->where('membership.wager_cents', 210)->where('membership.level', 1));
    }
}
