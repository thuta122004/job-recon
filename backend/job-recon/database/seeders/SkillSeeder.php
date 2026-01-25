<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            [
                'name' => 'Laravel Framework',
                'desc' => 'Expertise in developing robust web applications using the Laravel PHP framework, including Eloquent, Blade, and Service Providers.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Vue.js Ecosystem',
                'desc' => 'Proficiency in building reactive user interfaces using Vue 3, Composition API, Pinia, and Vue Router.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Tailwind CSS',
                'desc' => 'Utility-first CSS framework for rapidly building custom user interfaces without leaving your HTML.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'RESTful API Design',
                'desc' => 'Architecture and implementation of standardized web services using JSON, status codes, and HTTP methods.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Git & Version Control',
                'desc' => 'Advanced knowledge of Git workflows, branching strategies, merging, and collaborative development via GitHub/GitLab.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Technical Writing',
                'desc' => 'The ability to document code, create user manuals, and explain complex technical concepts to non-technical stakeholders.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Problem Solving',
                'desc' => 'Analytical thinking and systematic approach to troubleshooting complex software bugs and logical hurdles.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'Database Management',
                'desc' => 'Design and optimization of relational databases like MySQL and PostgreSQL, including indexing and query optimization.',
                'status' => 'ACTIVE',
            ],
            [
                'name' => 'UI/UX Design Principles',
                'desc' => 'Understanding of user-centered design, wireframing, and creating aesthetically pleasing, functional layouts.',
                'status' => 'INACTIVE',
            ],
            [
                'name' => 'Project Management',
                'desc' => 'Leading development teams using Agile and Scrum methodologies to ensure timely delivery of project milestones.',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($skills as $skill) {
            Skill::updateOrCreate(['name' => $skill['name']], $skill);
        }
    }
}
