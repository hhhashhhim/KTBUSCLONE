<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
       User::create([
            'name' => 'Admin User',
            'company_id' => 0,
            'terminal_id' => null,
            'role_id' => 1,
            'online_user' => 0,
            'check_allowed_seats' => 1,
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'contact' => '03123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
