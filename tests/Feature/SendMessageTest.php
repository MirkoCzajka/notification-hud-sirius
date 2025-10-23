<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Service;
use App\Models\MessageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\ActsAsJwtUser;

class SendMessageTest extends TestCase
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
        $this->app->bind(\App\Services\TelegramService::class, fn() => new class {
            public function __construct(...$args) {}
            public function postMessage(...$args) { return ['ok' => false, 'description' => 'fail']; }
        });
    }

    public function test_send_to_multiple_services_persists_each(): void
    {
        $role = UserRole::factory()->user()->create();
        $user = User::factory()->state(['role_id'=>$role->id])->create(['password'=>bcrypt('password')]);
        $headers = $this->withJwt($user);

        Service::factory()->state(['name'=>'slack','endpoint'=>'TOKEN1'])->create();
        Service::factory()->state(['name'=>'telegram','endpoint'=>'TOKEN2'])->create();

        // Fakes de servicios externos
        $this->app->bind(\App\Services\SlackService::class, fn() => new class {
            public function __construct($e=null) {}
            public function postMessage(...$args) { return ['ok'=>true,'ts'=>'1']; }
        });
        $this->app->bind(\App\Services\TelegramService::class, fn() => new class {
            public function __construct($e=null) {}
            public function postMessage(...$args) { return ['ok'=>false,'description'=>'fail']; }
        });

        $res = $this->postJson('/api/send_message', [
            'content' => 'hola',
            'destinations' => ['slack','telegram'],
        ], $headers)->assertStatus(201)->json();

        $this->assertTrue($res['results']['slack']['ok']);
        $this->assertFalse($res['results']['telegram']['ok']);
    }

    public function test_validation_invalid_service_returns_422(): void
    {
        $role = UserRole::factory()->user()->create();
        $user = \App\Models\User::factory()->state(['role_id'=>$role->id])->create(['password'=>bcrypt('password')]);
        $headers = $this->withJwt($user);

        $this->postJson('/api/send_message', [
            'content' => 'hola',
            'destinations' => ['noexiste'],
        ], $headers)->assertStatus(422);
    }
}
