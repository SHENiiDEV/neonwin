<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Game> */
class GameFactory extends Factory
{
    public function definition(): array
    {
        $code = 'TEST_'.Str::upper(Str::random(10));

        return ['provider_code' => fn () => Provider::factory()->create()->code, 'game_code' => $code, 'slug' => Str::lower($code), 'name' => 'Test game', 'category' => 'slots', 'status' => true];
    }
}
