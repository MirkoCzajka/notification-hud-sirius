<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessageStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['key'=>'pending','name'=>'Pending','description'=>'Pending send'],
            ['key'=>'success','name'=>'Success','description'=>'Success sent to provider'],
            ['key'=>'failed','name'=>'Failed','description'=>'Send failed'],
        ];
        foreach ($rows as $r) {
            DB::table('message_statuses')->updateOrInsert(
                ['key'=>$r['key']],
                array_merge($r, ['created_at'=>$now,'updated_at'=>$now])
            );
        }
    }
}
