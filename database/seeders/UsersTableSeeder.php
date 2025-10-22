<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = DB::table('user_roles')->where('type', 'admin')->value('id');

        if (!$adminRoleId) {
            $this->call(UserRolesTableSeeder::class);
            $adminRoleId = DB::table('user_roles')->where('type', 'admin')->value('id');
        }

        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin123'),
                'role_id'  => $adminRoleId,
                'remember_token' => \Illuminate\Support\Str::random(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
