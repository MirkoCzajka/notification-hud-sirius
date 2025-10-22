<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRolesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_roles')->updateOrInsert(
            ['type' => 'admin'],
            ['description' => 'Administrador', 'daily_msg_limit' => 0, 'permissions' => json_encode(['*']), 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('user_roles')->updateOrInsert(
            ['type' => 'user'],
            ['description' => 'Usuario estándar', 'daily_msg_limit' => 100, 'permissions' => json_encode([]), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
