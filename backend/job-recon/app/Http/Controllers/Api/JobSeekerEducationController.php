<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerEducation;
use App\Models\JobSeekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobSeekerEducationController extends Controller
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

        $educations = JobSeekerEducation::where('job_seeker_profile_id', $id)
            ->orderBy('start_year', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Education history retrieved successfully',
            'data'    => [
                'profile'    => $profile,
                'educations' => $educations
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
            'institution'           => 'required|string|max:255',
            'qualification_name'    => 'required|string|max:255',
            'field_of_study'        => 'nullable|string|max:255',
            'education_level'       => 'required|in:CERTIFICATE,DIPLOMA,BACHELOR,MASTER,PHD',
            'start_year'            => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'end_year'              => 'nullable|integer|after_or_equal:start_year|max:' . (date('Y') + 10),
            'description'           => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $profileId = $request->job_seeker_profile_id;
            $start = (int)$request->start_year;
            $end   = $request->end_year ? (int)$request->end_year : (int)date('Y');

            if (empty($request->end_year)) {
                $exists = JobSeekerEducation::where('job_seeker_profile_id', $profileId)
                    ->whereNull('end_year')
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('end_year', 'You already have an ongoing education record. Please provide a completion year for previous ones.');
                }
            }

            $overlapExists = JobSeekerEducation::where('job_seeker_profile_id', $profileId)
                ->where(function ($query) use ($start, $end) {
                    $query->where('start_year', '<=', $end)
                        ->where(function ($q) use ($start) {
                            $q->whereNull('end_year')
                                ->orWhere('end_year', '>=', $start);
                        });
                })->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_year', 'This academic period overlaps with an existing education record.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['end_year'] = !empty($data['end_year']) ? $data['end_year'] : null;
        $data['field_of_study'] = !empty($data['field_of_study']) ? $data['field_of_study'] : null;

        $education = JobSeekerEducation::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Education record added successfully',
            'data'    => $education
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
        $education = JobSeekerEducation::find($id);

        if (!$education) {
            return response()->json(['status' => false, 'message' => 'Education record not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'institution'        => 'required|string|max:255',
            'qualification_name' => 'required|string|max:255',
            'field_of_study'     => 'nullable|string|max:255',
            'education_level'    => 'required|in:CERTIFICATE,DIPLOMA,BACHELOR,MASTER,PHD',
            'start_year'         => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'end_year'           => 'nullable|integer|after_or_equal:start_year|max:' . (date('Y') + 10),
            'description'        => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request, $education) {
            $profileId = $education->job_seeker_profile_id;
            $start = (int)$request->start_year;
            $end   = $request->end_year ? (int)$request->end_year : (int)date('Y');

            if (empty($request->end_year)) {
                $exists = JobSeekerEducation::where('job_seeker_profile_id', $profileId)
                    ->where('id', '!=', $education->id)
                    ->whereNull('end_year')
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('end_year', 'You already have another ongoing education record.');
                }
            }

            $overlapExists = JobSeekerEducation::where('job_seeker_profile_id', $profileId)
                ->where('id', '!=', $education->id)
                ->where(function ($query) use ($start, $end) {
                    $query->where('start_year', '<=', $end)
                        ->where(function ($q) use ($start) {
                            $q->whereNull('end_year')
                                ->orWhere('end_year', '>=', $start);
                        });
                })->exists();

            if ($overlapExists) {
                $validator->errors()->add('start_year', 'This academic period overlaps with an existing education record.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['end_year'] = !empty($data['end_year']) ? $data['end_year'] : null;
        $data['field_of_study'] = !empty($data['field_of_study']) ? $data['field_of_study'] : null;

        $education->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Education record updated successfully',
            'data'    => $education
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $education = JobSeekerEducation::find($id);

        if (!$education) {
            return response()->json(['status' => false, 'message' => 'Education record not found'], 404);
        }

        $education->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Education record deleted successfully'
        ], 200);
    }
}
