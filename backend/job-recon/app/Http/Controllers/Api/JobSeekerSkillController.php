<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobSeekerSkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $profile = JobSeekerProfile::with(['user', 'skills'])->find($id);

        if (!$profile) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Skills retrieved successfully',
            'data'    => [
                'profile' => $profile,
                'skills'  => $profile->skills
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_seeker_profile_id' => 'required|integer|exists:job_seeker_profiles,id',
            'skills'                => 'present|array',
            'skills.*.id'           => 'required|integer|exists:skills,id',
            'skills.*.proficiency'  => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = JobSeekerProfile::findOrFail($request->job_seeker_profile_id);
        
        $syncData = [];
        
        if (!empty($request->skills)) {
            foreach ($request->skills as $skill) {
                $syncData[$skill['id']] = ['proficiency' => $skill['proficiency']];
            }
        }

        $profile->skills()->sync($syncData);

        return response()->json([
            'status'  => true,
            'message' => 'Skills updated successfully',
            'data'    => $profile->load('skills')
        ], 200);
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
    public function destroy($profileId, $skillId)
    {
        $profile = JobSeekerProfile::find($profileId);

        if (!$profile) {
            return response()->json([
                'status'  => false, 
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->skills()->detach($skillId);

        return response()->json([
            'status'  => true,
            'message' => 'Skill removed successfully'
        ], 200);
    }
}
