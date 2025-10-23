<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Testing\TestResponse;

trait ActsAsJwtUser
{
    protected function jwtFor(User $user): string
    {
        $res = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $res->assertStatus(200);
        return $res->json('access_token');
    }

    protected function withJwt(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->jwtFor($user), 'Accept'=>'application/json'];
    }
}
