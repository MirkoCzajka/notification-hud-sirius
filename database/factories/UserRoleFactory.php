<?php

namespace Database\Factories;

use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    public function definition()
    {
        return [
            'type' => $this->faker->unique()->randomElement(['user','admin']),
            'description' => $this->faker->sentence(),
            'daily_msg_limit' => 100,
            'permissions' => [],
        ];
    }

    public function admin()
    {
        return $this->state(fn() => [
            'type' => 'admin',
            'description' => 'Administrator',
            'daily_msg_limit' => 1000,
        ]);
    }

    public function user()
    {
        return $this->state(fn() => [
            'type' => 'user',
            'description' => 'Regular user',
            'daily_msg_limit' => 100,
        ]);
    }
}

