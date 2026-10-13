<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminWalletTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function adjustment(User $player): array
    {
        return ['user_id' => $player->id, 'target_email' => $player->email, 'direction' => 'credit', 'amount' => '50.00', 'reason' => 'Approved customer service credit', 'request_id' => (string) Str::uuid(), 'confirmed' => true];
    }

    public function test_admin_can_search_and_select_player_wallet(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['email' => 'wallet-player@example.com']);

        $this->actingAs($admin)->get('/admin/wallets?q=wallet-player&user_id='.$player->id)
            ->assertInertia(fn ($page) => $page->component('AdminWallets')->has('users', 1)->where('selectedUser.email', $player->email));
    }

    public function test_only_admin_can_access_or_adjust_wallets(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)->get('/admin/wallets')->assertForbidden();
        $this->post('/admin/wallets/adjust', $this->adjustment($player))->assertForbidden();
        $this->assertSame(0, $player->fresh()->eur_balance_cents);
    }

    public function test_admin_credit_and_debit_are_audited_and_idempotent(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['eur_balance_cents' => 1000]);
        $credit = $this->adjustment($player);

        $this->actingAs($admin)->post('/admin/wallets/adjust', $credit)->assertSessionHasNoErrors();
        $this->post('/admin/wallets/adjust', $credit)->assertSessionHasNoErrors();
        $this->assertSame(6000, $player->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 1);
        $this->assertDatabaseHas('wallet_entries', ['user_id' => $player->id, 'kind' => 'admin_credit', 'delta_cents' => 5000]);

        $debit = array_replace($credit, ['direction' => 'debit', 'amount' => '12.00', 'reason' => 'Correcting an accidental overcredit', 'request_id' => (string) Str::uuid()]);
        $this->post('/admin/wallets/adjust', $debit)->assertSessionHasNoErrors();
        $this->post('/admin/wallets/adjust', $debit)->assertSessionHasNoErrors();
        $this->assertSame(4800, $player->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 2);
        $this->assertSame(2, AdminAudit::where('action', 'admin_wallet_adjusted')->where('actor_id', $admin->id)->count());
        $this->assertSame($player->id, AdminAudit::where('action', 'admin_wallet_adjusted')->firstOrFail()->after['target_user_id']);
    }

    public function test_confirmation_email_overdraft_and_reused_request_are_rejected(): void
    {
        $admin = $this->admin();
        $player = User::factory()->create(['eur_balance_cents' => 100]);
        $request = $this->adjustment($player);

        $this->actingAs($admin)->postJson('/admin/wallets/adjust', array_replace($request, ['target_email' => 'someone@example.com']))->assertUnprocessable()->assertJsonValidationErrors('target_email');
        $this->postJson('/admin/wallets/adjust', array_replace($request, ['direction' => 'debit']))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/admin/wallets/adjust', array_replace($request, ['confirmed' => false]))->assertUnprocessable()->assertJsonValidationErrors('confirmed');
        $this->assertSame(100, $player->fresh()->eur_balance_cents);

        $this->post('/admin/wallets/adjust', $request)->assertSessionHasNoErrors();
        $this->postJson('/admin/wallets/adjust', array_replace($request, ['amount' => '60.00']))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->assertSame(5100, $player->fresh()->eur_balance_cents);
    }
}
