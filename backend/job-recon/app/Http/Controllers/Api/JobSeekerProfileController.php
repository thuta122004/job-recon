<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\JobCategory;
use App\Models\JobPost;
use App\Models\JobSeekerProfile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobSeekerProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getRecommendations(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $profile = JobSeekerProfile::with('skills')->where('user_id', $user->id)->first();

            if (!$profile || !$profile->skills || $profile->skills->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'data' => [],
                    'message' => 'Profile or skills not found'
                ]);
            }

            $seekerSkillIds = $profile->skills->pluck('id')->toArray();

            $recommendedJobs = JobPost::with(['employer', 'category', 'skills'])
                ->where('status', 'OPEN')
                ->whereHas('skills', function ($query) use ($seekerSkillIds) {
                    $query->whereIn('skills.id', $seekerSkillIds);
                })
                ->get()
                ->map(function ($job) use ($seekerSkillIds) {
                    $jobSkillIds = $job->skills->pluck('id')->toArray();
                    
                    $matches = array_intersect($seekerSkillIds, $jobSkillIds);
                    $matchCount = count($matches);
                    $totalJobSkills = count($jobSkillIds);

                    $job->match_percentage = $totalJobSkills > 0 
                        ? round(($matchCount / $totalJobSkills) * 100) 
                        : 0;

                    return $job;
                })
                ->sortByDesc('match_percentage')
                ->take(6)
                ->values();

            return response()->json([
                'status' => true,
                'data' => $recommendedJobs
            ]);

        } catch (\Exception $e) {
            Log::error("Recommendation Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function getHomeData()
    {
        return response()->json([
            'stats' => [
                'activeJobs' => JobPost::where('status', 'OPEN')->count(),
                'topCompanies' => EmployerProfile::count(),
            ],
            'categories' => JobCategory::withCount(['jobPosts' => fn($q) => $q->where('status', 'OPEN')])
                ->having('job_posts_count', '>', 0)
                ->orderBy('job_posts_count', 'desc')
                ->take(8)
                ->get(),
            'featuredJobs' => JobPost::with(['employer', 'category'])
                ->where('status', 'OPEN')
                ->latest()
                ->limit(6)
                ->get()
        ]);
    }

    public function toggleSaveJob(Request $request, $jobId)
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                return response()->json(['message' => 'Profile not found'], 404);
            }

            $status = $profile->savedJobs()->toggle($jobId);

            $isSaved = count($status['attached']) > 0;

            return response()->json([
                'status' => true,
                'is_saved' => $isSaved,
                'message' => $isSaved ? 'Job saved successfully' : 'Job removed from saved'
            ]);

        } catch (Exception $e) {
            return response()->json(['message' => 'Error toggling save status'], 500);
        }
    }

    public function getSavedJobs($id) 
    {
        $profile = JobSeekerProfile::with(['savedJobs.employer', 'savedJobs.category'])
            ->find($id);

        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $profile->savedJobs
        ]);
    }

    public function index()
    {
        $profiles = JobSeekerProfile::with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'ACTIVE');
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Profiles retrieved successfully',
            'data' => $profiles
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'            => 'required|integer|exists:users,id|unique:job_seeker_profiles,user_id',
            'headline'           => 'nullable|string|max:255',
            'summary'            => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'current_position'   => 'nullable|string|max:255',
            'experience_years'   => 'nullable|integer|min:0',
            'profile_visibility' => 'required|in:PUBLIC,PRIVATE',
            'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
            'resume'             => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['profile_picture', 'resume']);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture_url'] = $request->file('profile_picture')->store('profiles/avatars', 'public');
        }

        if ($request->hasFile('resume')) {
            $data['resume_url'] = $request->file('resume')->store('profiles/resumes', 'public');
        }

        $profile = JobSeekerProfile::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Profile created successfully',
            'data' => $profile
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $profile = JobSeekerProfile::with(['user', 'skills', 'experiences', 'educations', 'savedJobs:id'])
            ->where('user_id', $id)
            ->first();

        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $profile
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $profile = JobSeekerProfile::find($id);

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id'            => 'required|integer|exists:users,id|unique:job_seeker_profiles,user_id,' . $profile->id,
            'headline'           => 'nullable|string|max:255',
            'summary'            => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'current_position'   => 'nullable|string|max:255',
            'experience_years'   => 'nullable|integer|min:0',
            'profile_visibility' => 'required|in:PUBLIC,PRIVATE',
            'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
            'resume'             => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'delete_picture'     => 'nullable', 
            'delete_resume'      => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['profile_picture', 'resume', 'delete_picture', 'delete_resume']);

        if ($request->hasFile('profile_picture')) {
            $oldPath = $profile->getRawOriginal('profile_picture_url');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['profile_picture_url'] = $request->file('profile_picture')->store('profiles/avatars', 'public');
        } 
        elseif ($request->boolean('delete_picture')) {
            $oldPath = $profile->getRawOriginal('profile_picture_url');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['profile_picture_url'] = null;
        }
        if ($request->hasFile('resume')) {
            $oldResumePath = $profile->getRawOriginal('resume_url');

            if ($oldResumePath && Storage::disk('public')->exists($oldResumePath)) {
                Storage::disk('public')->delete($oldResumePath);
            }
            $data['resume_url'] = $request->file('resume')->store('profiles/resumes', 'public');
        }
        elseif ($request->boolean('delete_resume')) {
            $oldResumePath = $profile->getRawOriginal('resume_url');
            
            if ($oldResumePath && Storage::disk('public')->exists($oldResumePath)) {
                Storage::disk('public')->delete($oldResumePath);
            }
            $data['resume_url'] = null;
        }

        $profile->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile->load('user')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $profile = JobSeekerProfile::find($id);

        if (!$profile) {
            return response()->json([
                'status'  => false,
                'message' => 'Profile not found',
            ], 404);
        }

        $newVisibility = ($profile->profile_visibility === 'PUBLIC') ? 'PRIVATE' : 'PUBLIC';
        
        $profile->update(['profile_visibility' => $newVisibility]);

        return response()->json([
            'status'  => true,
            'message' => 'Profile visibility set to ' . $newVisibility,
            'data'    => $profile
        ], 200);
    }
}
