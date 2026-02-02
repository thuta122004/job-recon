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
        return response()->json([
            'overview' => [
                'totalUsers' => User::count(),
                'activeJobs' => JobPost::where('status', 'OPEN')->count(),
                'totalEmployers' => EmployerProfile::count(),
                'talentPool' => JobSeekerProfile::count(),
            ],
            'talentInsights' => [
                'seekersWithSkills' => DB::table('job_seeker_skills')->distinct('job_seeker_profile_id')->count(),
                'topSkills' => DB::table('skills')
                    ->join('job_seeker_skills', 'skills.id', '=', 'job_seeker_skills.skill_id')
                    ->select('skills.name', DB::raw('count(*) as count'))
                    ->groupBy('skills.name', 'skills.id')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
            ],
            'marketActivity' => [
                'categoryDistribution' => DB::table('job_categories')
                    ->leftJoin('job_posts', 'job_categories.id', '=', 'job_posts.job_category_id')
                    ->select('job_categories.name', DB::raw('count(job_posts.id) as jobs_count'))
                    ->groupBy('job_categories.name', 'job_categories.id')
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
