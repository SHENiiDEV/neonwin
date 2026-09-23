<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCrate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CratesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_crates(): void
    {
        $user = User::factory()->create();
        UserCrate::create([
            'user_id' => $user->id,
            'type' => 'recruit',
            'name' => 'Recruit Crate',
            'tier' => 'bronze',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson('/api/crates');
        $response->assertOk();
        $response->assertJson([
            'pending_count' => 1,
        ]);
    }

    public function test_user_can_open_crate_and_receive_reward(): void
    {
        $user = User::factory()->create(['game_balance' => 10.00]);
        $crate = UserCrate::create([
            'user_id' => $user->id,
            'type' => 'ronin',
            'name' => 'Ronin Gold Crate',
            'tier' => 'gold',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson("/api/crates/{$crate->id}/open");
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'reward_sc',
            'reward_xp',
            'new_balance',
        ]);

        $crate->refresh();
        $this->assertEquals('opened', $crate->status);
        $this->assertGreaterThan(0, $crate->reward_sc);
        $this->assertNotNull($crate->opened_at);

        $user->refresh();
        $this->assertGreaterThan(10.00, $user->game_balance);
    }

    public function test_user_cannot_open_same_crate_twice(): void
    {
        $user = User::factory()->create();
        $crate = UserCrate::create([
            'user_id' => $user->id,
            'type' => 'bronze',
            'name' => 'Bronze Crate',
            'tier' => 'bronze',
            'status' => 'opened',
            'reward_sc' => 5.00,
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/crates/{$crate->id}/open");
        $response->assertStatus(422);
    }
}
