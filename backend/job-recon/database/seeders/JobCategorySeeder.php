<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Information Technology',
                'icon_class' => 'fa-solid fa-code',
                'description' => 'Software development, cybersecurity, cloud computing, and infrastructure management.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Healthcare & Medical',
                'icon_class' => 'fa-solid fa-stethoscope',
                'description' => 'Nursing, clinical research, medical practice, and healthcare administration services.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Finance & Accounting',
                'icon_class' => 'fa-solid fa-chart-line',
                'description' => 'Banking, investment management, corporate finance, and tax consulting.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Creative Arts & Design',
                'icon_class' => 'fa-solid fa-palette',
                'description' => 'Graphic design, UI/UX, motion graphics, and multimedia production.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Education & Training',
                'icon_class' => 'fa-solid fa-graduation-cap',
                'description' => 'Academic teaching, professional coaching, and educational technology development.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Sales & Marketing',
                'icon_class' => 'fa-solid fa-bullhorn',
                'description' => 'Digital marketing, strategic advertising, brand management, and sales operations.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Engineering',
                'icon_class' => 'fa-solid fa-gears',
                'description' => 'Civil, mechanical, electrical, and structural engineering projects.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Logistics & Supply Chain',
                'icon_class' => 'fa-solid fa-truck-fast',
                'description' => 'Inventory management, distribution, global shipping, and procurement.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Customer Service',
                'icon_class' => 'fa-solid fa-headset',
                'description' => 'Client relations, support desk operations, and service excellence roles.',
                'status' => 'INACTIVE',
            ],
            [
                'name' => 'Human Resources',
                'icon_class' => 'fa-solid fa-user-tie',
                'description' => 'Recruitment, organizational development, and employee relations management.',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($categories as $cat) {
            JobCategory::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'icon_class' => $cat['icon_class'],
                    'description' => $cat['description'],
                    'slug' => Str::slug($cat['name']),
                    'status' => $cat['status'],
                ]
            );
        }
    }
}
