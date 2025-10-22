<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->updateOrInsert(
            ['name' => 'slack'],
            [
                'endpoint'   => 'https://slack.com/api/',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
        DB::table('services')->updateOrInsert(
            ['name' => 'telegram'],
            [
                'endpoint'   => 'https://api.telegram.org/',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
        DB::table('services')->updateOrInsert(
            ['name' => 'discord'],
            [
                'endpoint'   => 'https://discord.com/api/v10/',
                'created_at' => now(), 'updated_at' => now(),
            ]
        );
    }
}
