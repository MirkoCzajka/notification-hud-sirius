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

class MessagesIndexFiltersTest extends TestCase
{
    use RefreshDatabase, ActsAsJwtUser;

    protected function setUp(): void
    {
        parent::setUp();
        MessageStatus::firstOrCreate(['key'=>'pending'],['name'=>'Pending']);
        MessageStatus::firstOrCreate(['key'=>'success'],['name'=>'Success']);
        MessageStatus::firstOrCreate(['key'=>'failed'],['name'=>'Failed']);
    }

    public function test_filters_by_status_service_and_date_range(): void
    {
        $role = UserRole::factory()->user()->create();
        $user = User::factory()->state(['role_id'=>$role->id])->create(['password'=>bcrypt('password')]);

        $slack = Service::factory()->state(['name'=>'slack'])->create();
        $telegram = Service::factory()->state(['name'=>'telegram'])->create();

        $successId = MessageStatus::where('key','success')->value('id');
        $pendingId = MessageStatus::where('key','pending')->value('id');

        // Crea varios mensajes
        Message::factory()->count(2)->create([
            'user_id' => $user->id, 'service_id'=>$slack->id, 'message_status_id'=>$successId,
            'created_at'=>Carbon::parse('2025-10-10')
        ]);
        Message::factory()->count(1)->create([
            'user_id' => $user->id, 'service_id'=>$telegram->id, 'message_status_id'=>$pendingId,
            'created_at'=>Carbon::parse('2025-10-15')
        ]);

        $headers = $this->withJwt($user);

        $res = $this->getJson('/api/messages?status[]=success&services[]=slack&from=2025-10-01&to=2025-10-21', $headers)
            ->assertStatus(200)->json();

        $this->assertGreaterThanOrEqual(1, $res['total']);
        foreach ($res['data'] as $row) {
            $this->assertEquals('slack', $row['service']['name']);
            $this->assertEquals('success', $row['status_ref']['key']);
        }
    }
}
