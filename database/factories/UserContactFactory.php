<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserContact>
 */
class UserContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => UserContact::TYPE_PHONE,
            'value' => '119'.fake()->unique()->numerify('########'),
        ];
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => UserContact::TYPE_EMAIL,
            'value' => fake()->unique()->safeEmail(),
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn () => [
            'is_emergency' => true,
            'relationship' => 'Mother',
            'contact_name' => fake()->name(),
        ]);
    }
}
