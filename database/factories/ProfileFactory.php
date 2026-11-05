<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->regexify('Perfil [a-z]{12}'),
            'description' => fake()->sentence(),
        ];
    }
}
