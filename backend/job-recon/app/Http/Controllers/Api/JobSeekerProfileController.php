<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\JobCategory;
use App\Models\JobPost;
use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobSeekerProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */

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
            'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
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
    public function show($slug)
    {
        $job = JobPost::with(['employer.user', 'category', 'skills'])
            ->where('status', 'OPEN')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($job);
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
            'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
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
