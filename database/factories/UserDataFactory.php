<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserData>
 */
class UserDataFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
