<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerExperience;
use App\Models\JobSeekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobSeekerExperienceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $profile = JobSeekerProfile::with('user')->find($id);

        if (!$profile) {
            return response()->json([
                'status'  => false,
                'message' => 'Profile not found',
            ], 404);
        }

        $experiences = JobSeekerExperience::where('job_seeker_profile_id', $id)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Work history retrieved successfully',
            'data'    => [
                'profile'     => $profile,
                'experiences' => $experiences
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
            'job_title'             => 'required|string|max:255',
            'company_name'          => 'required|string|max:255',
            'location'              => 'nullable|string|max:255',
            'employment_type'       => 'required|in:FULL-TIME,PART-TIME',
            'start_date'            => 'required|date|before_or_equal:today',
            'end_date'              => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
            'description'           => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $profileId = $request->job_seeker_profile_id;
            
            if (is_null($request->end_date)) {
                $currentRoleExists = JobSeekerExperience::where('job_seeker_profile_id', $profileId)
                    ->whereNull('end_date')
                    ->exists();

                if ($currentRoleExists) {
                    $validator->errors()->add('end_date', 'You already have an active "Current" role. Please provide an end date for previous roles first.');
                }
            }

            $start = $request->start_date;
            $end   = $request->end_date ?? now()->toDateString();

            $overlapExists = JobSeekerExperience::where('job_seeker_profile_id', $profileId)
                ->where(function ($query) use ($start, $end) {
                    $query->where('start_date', '<=', $end)
                        ->where(function ($q) use ($start) {
                            $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $start);
                        });
                })
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_date', 'This job experience overlaps with an existing one.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : null;
        $data['location'] = !empty($data['location']) ? $data['location'] : null;

        $experience = JobSeekerExperience::create($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Experience record added successfully',
            'data'    => $experience
        ], 201);
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
    public function update(Request $request, $id)
    {
        $experience = JobSeekerExperience::find($id);

        if (!$experience) {
            return response()->json([
                'status'  => false, 
                'message' => 'Experience record not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'job_title'    => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'location'              => 'nullable|string|max:255',
            'employment_type'       => 'required|in:FULL-TIME,PART-TIME',
            'start_date'   => 'required|date|before_or_equal:today',
            'end_date'     => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
            'description'  => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request, $experience) {
            $profileId = $experience->job_seeker_profile_id;

            if (is_null($request->end_date)) {
                $currentRoleExists = JobSeekerExperience::where('job_seeker_profile_id', $profileId)
                    ->whereNull('end_date')
                    ->where('id', '!=', $experience->id)
                    ->exists();

                if ($currentRoleExists) {
                    $validator->errors()->add('end_date', 'You already have another active role. Only one current role is allowed.');
                }
            }

            $start = $request->start_date;
            $end   = $request->end_date ?? now()->toDateString();

            $overlapExists = JobSeekerExperience::where('job_seeker_profile_id', $profileId)
                ->where('id', '!=', $experience->id)
                ->where(function ($query) use ($start, $end) {
                    $query->where('start_date', '<=', $end)
                        ->where(function ($q) use ($start) {
                            $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $start);
                        });
                })
                ->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_date', 'This job experience overlaps with an existing one.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : null;
        $data['location'] = !empty($data['location']) ? $data['location'] : null;

        $experience->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Experience updated successfully',
            'data'    => $experience
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $experience = JobSeekerExperience::find($id);

        if (!$experience) {
            return response()->json([
                'status'  => false, 
                'message' => 'Experience record not found'
            ], 404);
        }

        $experience->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Experience record deleted successfully'
        ], 200);
    }
}
