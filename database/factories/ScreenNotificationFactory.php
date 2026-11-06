<?php

namespace Database\Factories;

use App\Models\ScreenNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScreenNotification>
 */
class ScreenNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(),
            'type' => 'info',
        ];
    }

    public function seen(): static
    {
        return $this->state(fn () => ['seen_at' => now()]);
    }
}
