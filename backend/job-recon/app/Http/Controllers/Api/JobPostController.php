<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\JobPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class JobPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobs = JobPost::with(['employer', 'category', 'skills'])
            ->where('status', 'OPEN')
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Active job postings retrieved successfully',
            'data'    => $jobs
        ], 200);
    }

    public function getByEmployer($id)
    {
        $profile = EmployerProfile::with('user')->find($id);

        if (!$profile) {
            return response()->json([
                'status'  => false,
                'message' => 'Employer profile not found',
            ], 404);
        }

        $jobs = JobPost::with(['category', 'skills'])
            ->where('employer_profile_id', $id)
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Employer job data retrieved',
            'data'    => [
                'profile' => $profile,
                'jobs'    => $jobs
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('skills') && is_string($request->skills)) {
            $decoded = json_decode($request->skills, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['skills' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'employer_profile_id' => 'required|exists:employer_profiles,id',
            'title'               => 'required|string|max:255',
            'job_category_id'     => 'required|exists:job_categories,id',
            'workplace_type'      => 'required|in:ON-SITE,REMOTE,HYBRID',
            'employment_type'     => 'required|in:FULL-TIME,PART-TIME,CONTRACT,TEMPORARY,INTERNSHIP,VOLUNTEER',
            'experience_level'    => 'required|in:NTRY-LEVEL,JUNIOR,MID-LEVEL,SENIOR,LEAD,DIRECTOR,EXECUTIVE',   
            'location'            => 'required|string|max:255',
            'expires_at'          => 'nullable|date|after_or_equal:today',
            'description'         => 'required|string',
            'responsibilities'    => 'nullable|string',
            'qualifications'      => 'nullable|string',
            'salary_min'          => 'nullable|numeric|min:0',
            'salary_max'          => 'nullable|numeric|gte:salary_min',
            'currency'            => 'nullable|string|max:10',
            'skills'              => 'nullable|array',
            'skills.*'            => 'exists:skills,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $validator) {
            $data = $validator->validated();
            
            $employer = EmployerProfile::findOrFail($data['employer_profile_id']);
            $companyName = $employer->company_name ?? 'hiring';

            $data['slug'] = Str::slug($companyName . '-' . $data['title']);
            $job = JobPost::create($data);

            $job->update([
                'slug' => $job->slug . '-' . $job->id
            ]);

            if (!empty($data['skills'])) {
                $job->skills()->sync($data['skills']);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Job published successfully',
                'data'    => $job->load(['skills', 'employer'])
            ], 201);
        });
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
        $job = JobPost::find($id);

        if (!$job) {
            return response()->json(['status' => false, 'message' => 'Job not found'], 404);
        }

        if ($request->has('skills') && is_string($request->skills)) {
            $decoded = json_decode($request->skills, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['skills' => $decoded]);
            }
        }

        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:255',
            'job_category_id'  => 'required|exists:job_categories,id',
            'workplace_type'   => 'required|in:ON-SITE,REMOTE,HYBRID',
            'employment_type'  => 'required|in:FULL-TIME,PART-TIME,CONTRACT,TEMPORARY,INTERNSHIP,VOLUNTEER',
            'experience_level' => 'required|in:ENTRY-LEVEL,JUNIOR,MID-LEVEL,SENIOR,LEAD,DIRECTOR,EXECUTIVE',   
            'location'         => 'required|string|max:255',
            'expires_at'       => 'nullable|date|after_or_equal:today',
            'description'      => 'required|string',
            'responsibilities' => 'nullable|string',
            'qualifications'   => 'nullable|string',
            'salary_min'       => 'nullable|numeric|min:0',
            'salary_max'       => 'nullable|numeric|gte:salary_min',
            'currency'         => 'nullable|string|max:10',
            'skills'           => 'nullable|array',
            'skills.*'         => 'exists:skills,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $validator, $job) {
            $data = $validator->validated();

            if ($data['title'] !== $job->title) {
                $companyName = $job->employer->company_name ?? 'hiring';
                
                $data['slug'] = Str::slug($companyName . '-' . $data['title'] . '-' . $job->id);
            }

            $job->update($data);

            if (isset($data['skills'])) {
                $job->skills()->sync($data['skills']);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Job updated successfully',
                'data'    => $job->load(['skills', 'employer'])
            ], 200);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $job = JobPost::find($id);

        if (!$job) {
            return response()->json([
                'status' => false, 
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status === 'OPEN') {
            return response()->json([
                'status' => false,
                'message' => 'Cannot archive an active vacancy.'
            ], 400);
        }

        $job->update(['status' => 'ARCHIVED']);

        return response()->json([
            'status'  => true,
            'message' => 'Job posting has been archived'
        ], 200);
    }

    public function restore($id)
    {
        $job = JobPost::find($id);

        if (!$job) {
            return response()->json([
                'status'  => false, 
                'message' => 'Job not found'
            ], 404);
        }

        if ($job->status !== 'ARCHIVED') {
            return response()->json([
                'status'  => false, 
                'message' => 'Job is not archived'
            ], 400);
        }

        $job->update(['status' => 'OPEN']);

        return response()->json([
            'status'  => true,
            'message' => 'Job posting has been restored successfully',
            'data'    => $job
        ], 200);
    }

    public function toggleVisibility($id)
    {
        $job = JobPost::findOrFail($id);
        $newStatus = ($job->status === 'OPEN') ? 'CLOSED' : 'OPEN';
        $job->update(['status' => $newStatus]);

        return response()->json([
            'status' => true,
            'message' => "Job status updated to $newStatus",
            'data' => ['status' => $newStatus]
        ], 200);
    }

    public function toggleSalaryVisibility($id)
    {
        $job = JobPost::findOrFail($id);
        
        $newStatus = !((bool) $job->salary_visible);

        $job->update([
            'salary_visible' => $newStatus
        ]);

        return response()->json([
            'status'  => true,
            'message' => $newStatus 
                ? "Salary scale for '{$job->title}' is now public" 
                : "Salary scale for '{$job->title}' is now hidden",
            'visible' => $newStatus,
            'job_id'  => $job->id
        ], 200);
    }
}
