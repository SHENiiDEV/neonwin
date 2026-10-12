<?php

namespace Database\Factories;

use App\Models\LaunchSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaunchSetting>
 */
class LaunchSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wheel_enabled' => true, 'wheel_prizes' => [['cents' => 10, 'weight' => 1]], 'rakeback_enabled' => false, 'telegram_enabled' => false,
        ];
    }
}
