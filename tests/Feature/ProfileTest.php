<?php

namespace Tests\Feature;

use App\Models\GameTransaction;
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
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile')
            ->has('stats')
            ->has('transactions', 1)
        );
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
