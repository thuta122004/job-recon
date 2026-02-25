<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\JobSeekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{

    public function checkApplicationStatus($jobPostId, $seekerId)
    {
        $exists = JobApplication::where('job_post_id', $jobPostId)
            ->where('job_seeker_id', $seekerId)
            ->exists();

        return response()->json([
            'applied' => $exists
        ]);
    }

    public function getByJob($jobId)
    {
        $applications = JobApplication::with(['jobSeeker.user', 'jobPost'])
            ->where('job_post_id', $jobId)
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Applications retrieved successfully',
            'data'    => $applications
        ], 200);
    }

    public function getBySeeker($seekerId)
    {
        $applications = JobApplication::with(['jobPost.employer'])
            ->where('job_seeker_id', $seekerId)
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Job applications retrieved successfully',
            'data'    => $applications
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_post_id'   => 'required|exists:job_posts,id',
            'job_seeker_id' => 'required|exists:job_seeker_profiles,id',
            'cover_letter'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $job = JobPost::with('skills')->findOrFail($request->job_post_id);
        $seeker = JobSeekerProfile::with('skills')->findOrFail($request->job_seeker_id);

        if (!$seeker->resume_url) {
            return response()->json([
                'status' => false,
                'message' => 'Your profile must have a resume before applying.'
            ], 403);
        }

        $jobSkillIds = $job->skills->pluck('id')->toArray();
        if (!empty($jobSkillIds)) {
            $seekerSkillIds = $seeker->skills->pluck('id')->toArray();
            $matches = array_intersect($jobSkillIds, $seekerSkillIds);
            
            if (count($matches) === 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have the required skills in your profile to apply for this role.'
                ], 403);
            }
        }

        $job = JobPost::findOrFail($request->job_post_id);

        if ($job->status !== 'OPEN') {
            return response()->json([
                'status'  => false,
                'message' => 'This vacancy is no longer accepting applications.'
            ], 400);
        }

        return DB::transaction(function () use ($request) {
            $application = JobApplication::create([
                'job_post_id'   => $request->job_post_id,
                'job_seeker_id' => $request->job_seeker_id,
                'cover_letter'  => $request->cover_letter,
                'status'        => 'PENDING',
            ]);

            JobPost::where('id', $request->job_post_id)->increment('application_count');

            return response()->json([
                'status'  => true,
                'message' => 'Application submitted successfully',
                'data'    => $application
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status'           => 'required|in:REVIEWING,SHORTLISTED,INTERVIEW_SCHEDULED,INTERVIEWED,OFFERED,REJECTED,WITHDRAWN',
            'employer_notes'   => 'nullable|string',
            'rejection_reason' => 'required_if:status,REJECTED|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $application) {
            $oldStatus = $application->status;
            $newStatus = $request->status;

            $application->update([
                'status'             => $newStatus,
                'employer_notes'     => $request->employer_notes,
                'rejection_reason'   => $request->rejection_reason,
                'last_status_change' => now(),
            ]);

            $activeStatuses = ['PENDING', 'REVIEWING', 'SHORTLISTED', 'INTERVIEW_SCHEDULED', 'INTERVIEWED'];
            $inactiveStatuses = ['REJECTED', 'WITHDRAWN'];

            if (in_array($oldStatus, $activeStatuses) && in_array($newStatus, $inactiveStatuses)) {
                JobPost::where('id', $application->job_post_id)->decrement('application_count');
            } 
            else if (in_array($oldStatus, $inactiveStatuses) && in_array($newStatus, $activeStatuses)) {
                JobPost::where('id', $application->job_post_id)->increment('application_count');
            }

            return response()->json([
                'status'  => true,
                'message' => "Application status updated to {$newStatus}",
                'data'    => $application
            ], 200);
        });
    }

    public function withdraw($id)
    {
        $application = JobApplication::findOrFail($id);

        if ($application->status === 'WITHDRAWN') {
            return response()->json(['status' => false, 'message' => 'Already withdrawn'], 400);
        }

        return DB::transaction(function () use ($application) {
            $application->update([
                'status'             => 'WITHDRAWN',
                'last_status_change' => now(),
            ]);

            JobPost::where('id', $application->job_post_id)->decrement('application_count');

            return response()->json([
                'status'  => true,
                'message' => 'Application withdrawn successfully'
            ], 200);
        });
    }

    public function reapply($id)
    {
        $application = JobApplication::findOrFail($id);

        if ($application->status !== 'WITHDRAWN') {
            return response()->json(['status' => false, 'message' => 'Application is already active'], 400);
        }

        return DB::transaction(function () use ($application) {
            $application->update([
                'status'             => 'PENDING',
                'last_status_change' => now(),
            ]);

            JobPost::where('id', $application->job_post_id)->increment('application_count');

            return response()->json([
                'status'  => true,
                'message' => 'Application restored successfully'
            ], 200);
        });
    }
}
