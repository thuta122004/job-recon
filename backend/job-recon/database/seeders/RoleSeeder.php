<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'desc' => 'System administrator with full access to manage users, jobs, and platform settings.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Job Seeker',
                'desc' => 'Standard user looking for opportunities, managing their profile, and applying for jobs.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Employer',
                'desc' => 'Company representative who can create company profiles, post jobs, and manage applications.',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
