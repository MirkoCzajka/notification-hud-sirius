<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_returns_jwt(): void
    {
        $role = UserRole::factory()->user()->create();

        $res = $this->postJson('/api/register', [
            'username' => 'mirko',
            'password' => 'password',
            'role_id'  => $role->id,
        ])->assertStatus(201);

        $this->postJson('/api/login', [
            'username' => 'mirko',
            'password' => 'password',
        ])->assertStatus(200)->assertJsonStructure(['access_token','token_type','expires_in']);
    }
}
