<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\JobPost;
use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function adminDashboard()
    {
        $thirtyDaysAgo = now()->subDays(30);

        $metrics = [
            'totalUsers' => User::where('status', 'ACTIVE'),
            'activeJobs' => JobPost::where('status', 'OPEN')
                ->whereHas('employer.user', fn($q) => $q->where('status', 'ACTIVE')),
            'totalEmployers' => EmployerProfile::whereHas('user', fn($q) => $q->where('status', 'ACTIVE')),
            'talentPool' => JobSeekerProfile::whereHas('user', fn($q) => $q->where('status', 'ACTIVE')),
        ];

        $overview = [];

        foreach ($metrics as $key => $query) {
            $currentCount = (clone $query)->count();
            $previousCount = (clone $query)->where('created_at', '<=', $thirtyDaysAgo)->count();
            
            $growth = 0;
            if ($previousCount > 0) {
                $growth = (($currentCount - $previousCount) / $previousCount) * 100;
            }

            $sparkline = [];
            for ($i = 6; $i >= 0; $i--) {
                $sparkline[] = (clone $query)->whereDate('created_at', '<=', now()->subDays($i))->count();
            }

            $overview[$key] = [
                'value' => $currentCount,
                'growth' => round($growth, 1),
                'sparkline' => $sparkline
            ];
        }

        return response()->json([
            'overview' => $overview,
            'talentInsights' => [
                'seekersWithSkills' => DB::table('job_seeker_skills')
                    ->join('job_seeker_profiles', 'job_seeker_skills.job_seeker_profile_id', '=', 'job_seeker_profiles.id')
                    ->join('users', 'job_seeker_profiles.user_id', '=', 'users.id')
                    ->where('users.status', 'ACTIVE')
                    ->distinct('job_seeker_profile_id')
                    ->count('job_seeker_profile_id'),
                'topSkills' => DB::table('skills')
                    ->join('job_seeker_skills', 'skills.id', '=', 'job_seeker_skills.skill_id')
                    ->join('job_seeker_profiles', 'job_seeker_skills.job_seeker_profile_id', '=', 'job_seeker_profiles.id')
                    ->join('users', 'job_seeker_profiles.user_id', '=', 'users.id')
                    ->where('users.status', 'ACTIVE')
                    ->select('skills.name', DB::raw('count(job_seeker_skills.skill_id) as count'))
                    ->groupBy('skills.id', 'skills.name')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
            ],
            'marketActivity' => [
                'categoryDistribution' => DB::table('job_categories')
                    ->leftJoin('job_posts', function($join) {
                        $join->on('job_categories.id', '=', 'job_posts.job_category_id')
                            ->where('job_posts.status', '=', 'OPEN');
                    })
                    ->leftJoin('employer_profiles', 'job_posts.employer_profile_id', '=', 'employer_profiles.id')
                    ->leftJoin('users', 'employer_profiles.user_id', '=', 'users.id')
                    ->select(
                        'job_categories.name', 
                        DB::raw('COUNT(CASE WHEN users.status = "ACTIVE" THEN job_posts.id END) as jobs_count')
                    )
                    ->groupBy('job_categories.id', 'job_categories.name')
                    ->orderBy('jobs_count', 'desc')
                    ->limit(5)
                    ->get()
            ]
        ]);
    }

    public function index()
    {
        //
    }  

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
