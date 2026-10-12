<?php

namespace Database\Factories;

use App\Models\RegistrationNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationNotice>
 */
class RegistrationNoticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'status' => 'pending',
        ];
    }
}
