<?php

namespace Tests\Feature;

use App\Models\GameTransaction;
use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_profile(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/');
    }

    public function test_authenticated_user_can_view_profile_with_stats(): void
    {
        $user = User::factory()->create([
            'name' => 'John',
            'surname' => 'Doe',
            'game_balance' => 250.00,
            'vip_xp' => 1250,
            'vip_level' => 1,
        ]);

        GameTransaction::create([
            'transaction_id' => 'tx-12345',
            'user_id' => $user->id,
            'game_code' => 'vs20sweetbonz',
            'provider_code' => 'PRAGMATIC',
            'bet_amount' => 5.00,
            'win_amount' => 25.00,
            'balance_before' => 230.00,
            'balance_after' => 250.00,
            'type' => 'win',
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile')
            ->has('stats')
            ->has('transactions', 1)
        );
    }

    public function test_empty_profile_has_no_invented_return_rate_and_only_its_own_verification(): void
    {
        $user = User::factory()->create();
        KycSubmission::factory()->create();

        $this->actingAs($user)->get('/profile?section=verification')->assertInertia(fn ($page) => $page
            ->where('stats.rtp', null)->where('verification', null)->where('history.total', 0));
    }

    public function test_game_history_is_paginated_and_excludes_other_users_and_legacy_currency(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 23) as $number) {
            GameTransaction::create(['user_id' => $number === 23 ? $other->id : $user->id,
                'transaction_id' => 'history-'.$number, 'provider_code' => 'PRAGMATIC', 'game_code' => 'game',
                'currency' => $number === 22 ? 'LEGACY' : 'EUR', 'bet_amount' => 1, 'win_amount' => 0,
                'balance_before' => 30, 'balance_after' => 29, 'type' => 'bet', 'raw_payload' => ['secret' => 'private']]);
        }

        $this->actingAs($user)->get('/profile?section=activity&page=2')->assertInertia(fn ($page) => $page
            ->has('transactions', 1)->where('history.total', 21)->where('history.page', 2)
            ->where('stats.total_bets', 21)->where('stats.rtp', 0)
            ->missing('transactions.0.raw_payload'));
    }

    public function test_user_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'name' => 'OldName',
            'surname' => 'OldSurname',
        ]);

        $response = $this->actingAs($user)->post('/profile', [
            'name' => 'NewName',
            'surname' => 'NewSurname',
            'phone' => '+447911999888',
            'street' => '10 Downing St',
            'city' => 'London',
            'postcode' => 'SW1A 2AA',
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertEquals('NewName', $user->name);
        $this->assertEquals('NewSurname', $user->surname);
        $this->assertEquals('+447911999888', $user->phone);
        $this->assertEquals('10 Downing St', $user->street);
    }
}
