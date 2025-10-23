<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Service;
use App\Models\MessageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\ActsAsJwtUser;

class DailyLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase, ActsAsJwtUser;

    protected function setUp(): void
    {
        parent::setUp();
        MessageStatus::firstOrCreate(['key'=>'pending'],['name'=>'Pending']);
        MessageStatus::firstOrCreate(['key'=>'success'],['name'=>'Success']);
        MessageStatus::firstOrCreate(['key'=>'failed'],['name'=>'Failed']);

        $this->app->bind(\App\Services\SlackService::class, fn() => new class {
            public function __construct(...$args) {}
            public function postMessage(...$args) { return ['ok' => true]; }
        });
    }

    public function test_user_exceeding_daily_limit_gets_429(): void
    {
        $role = UserRole::factory()->state(['type'=>'user','daily_msg_limit'=>1])->create();
        $user = User::factory()->state(['role_id'=>$role->id])->create(['password'=>bcrypt('password')]);
        $headers = $this->withJwt($user);

        Service::factory()->state(['name'=>'slack'])->create();

        // primer envío OK (fingimos éxito)
        // Para aislar HTTP externos, bind de SlackService a un fake:
        $this->app->bind(\App\Services\SlackService::class, fn() => new class {
            public function postMessage(...$args) { return ['ok'=>true]; }
        });

        $this->postJson('/api/send_message', [
            'content' => 'hola',
            'destinations' => ['slack'],
        ], $headers)->assertStatus(201);

        // segundo envío debería 429 (middleware)
        $this->postJson('/api/send_message', [
            'content' => 'hola 2',
            'destinations' => ['slack'],
        ], $headers)->assertStatus(429);
    }
}
