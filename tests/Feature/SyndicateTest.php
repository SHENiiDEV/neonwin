<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCrate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyndicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_syndicate_dashboard_data(): void
    {
        $user = User::factory()->create([
            'referral_code' => 'NW-REF-MASTER',
        ]);

        $recruit = User::factory()->create([
            'referred_by_id' => $user->id,
            'name' => 'CyberRecruit',
        ]);

        $response = $this->actingAs($user)->getJson('/api/syndicate');
        $response->assertOk();
        $response->assertJson([
            'referral_code' => 'NW-REF-MASTER',
            'total_referrals' => 1,
            'total_earned_sc' => 0,
        ]);
        $response->assertJsonStructure([
            'referral_code',
            'referral_url',
            'total_referrals',
            'total_earned_sc',
            'referrals' => [
                '*' => ['id', 'name', 'user_code', 'created_at', 'vip_level'],
            ],
            'earnings_history',
        ]);
    }

    public function test_registration_with_referral_code_rewards_referrer_and_creates_crate(): void
    {
        $referrer = User::factory()->create([
            'referral_code' => 'NW-REF-HERO',
            'game_balance' => 10.00,
        ]);

        $response = $this->post('/register', [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'recruit@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone' => '+1234567890',
            'date_of_birth' => '1995-05-15',
            'street' => '123 Cyber Way',
            'city' => 'Neo Tokyo',
            'country' => 'Germany',
            'postcode' => '10115',
            'terms' => true,
            'ref' => 'NW-REF-HERO',
        ]);

        $response->assertSessionHasNoErrors();

        $referrer->refresh();
        $this->assertEquals(10.50, $referrer->game_balance);

        $newPlayer = User::where('email', 'recruit@example.com')->first();
        $this->assertNotNull($newPlayer);
        $this->assertEquals($referrer->id, $newPlayer->referred_by_id);

        $crate = UserCrate::where('user_id', $newPlayer->id)->where('type', 'recruit')->first();
        $this->assertNotNull($crate);
        $this->assertEquals('pending', $crate->status);
    }
}
