<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'role_id' => 1,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@jobrecon.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '09123456789',
            'status' => 'ACTIVE',
        ]);

        User::create([
            'role_id' => 2,
            'first_name' => 'Jane',
            'last_name' => 'Seeker',
            'email' => 'seeker@jobrecon.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '09555444333',
            'status' => 'ACTIVE',
        ]);

        User::create([
            'role_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Employer',
            'email' => 'employer@jobrecon.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '09987654321',
            'status' => 'ACTIVE',
        ]);
    }
}
