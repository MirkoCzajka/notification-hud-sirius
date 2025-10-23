<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'role_id'  => UserRole::factory()->user(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role_id' => UserRole::factory()->admin()]);
    }
}
