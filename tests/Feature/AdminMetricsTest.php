<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Service;
use App\Models\Message;
use App\Models\MessageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\ActsAsJwtUser;
use Illuminate\Support\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AdminMetricsTest extends TestCase
{
    use RefreshDatabase, ActsAsJwtUser;

    public function test_admin_can_see_metrics(): void
    {
        $this->seedBase();

        $adminRole = UserRole::firstWhere('type','admin');
        $admin = User::factory()->state(['role_id'=>$adminRole->id])->create();
        $admin->load('role');

        $service = Service::factory()->state(['name'=>'slack'])->create();
        $successId = MessageStatus::where('key','success')->value('id');
        Message::factory()->count(3)->create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'message_status_id'=> $successId,
            'created_at' => \Illuminate\Support\Carbon::today(),
        ]);

        $headers = $this->withJwt($admin);

        $res = $this->withHeaders($headers)
            ->getJson('/api/admin/metrics/messages')
            ->assertStatus(200);
        
        $payload = $res->json();

        $todayStr = \Illuminate\Support\Carbon::today()->toDateString();
        $this->assertSame($todayStr, data_get($payload, 'date'));

        $perUserRow = collect(data_get($payload, 'metrics', []))
            ->first(fn ($r) => (int)($r['user_id'] ?? 0) === (int)$admin->id);
        
        $this->assertSame('admin', $perUserRow['role'] ?? null);
        $this->assertSame($admin->username, $perUserRow['username'] ?? null);

        $this->assertSame(3, (int)($perUserRow['today_sent']  ?? -1));
        $this->assertSame(3, (int)($perUserRow['total_sent']  ?? -1));
        $this->assertSame(3, (int)($perUserRow['today_tries'] ?? -1));
        $this->assertSame(3, (int)($perUserRow['total_tries'] ?? -1));

        $expectedRemaining = (int)$admin->role->daily_msg_limit - 3;
        $this->assertSame($expectedRemaining, (int)($perUserRow['remaining_today'] ?? -1));
    }

    public function test_user_gets_403_on_admin_metrics(): void
    {
        $this->seedBase();

        $userRole = UserRole::firstWhere('type','user');
        $user = User::factory()->state(['role_id'=>$userRole->id])->create();
        $user->load('role');

        $headers = $this->withJwt($user);

        $this->withHeaders($headers)
            ->getJson('/api/admin/metrics/messages')
            ->assertStatus(403);
    }

    private function seedBase(): void
    {
        MessageStatus::firstOrCreate(['key'=>'pending'], ['name'=>'Pending']);
        MessageStatus::firstOrCreate(['key'=>'success'], ['name'=>'Success']);
        MessageStatus::firstOrCreate(['key'=>'failed'],  ['name'=>'Failed']);

        UserRole::firstOrCreate(['type'=>'admin'], ['description'=>'Administrator','daily_msg_limit'=>1000,'permissions'=>[]]);
        UserRole::firstOrCreate(['type'=>'user'],  ['description'=>'Regular user','daily_msg_limit'=>100,'permissions'=>[]]);
    }
}
