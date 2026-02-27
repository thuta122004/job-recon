<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InterviewSchedule;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_application_id' => 'required|exists:job_applications,id',
            'title' => 'required|string|max:255',
            'scheduled_at' => 'required|date',
            'location_url' => 'required|string',
            'type' => 'required|in:ONLINE,IN-PERSON,PHONE',
        ]);

        return DB::transaction(function () use ($validated) {
            $interview = InterviewSchedule::updateOrCreate(
                ['job_application_id' => $validated['job_application_id']],
                [
                    'title' => $validated['title'],
                    'scheduled_at' => $validated['scheduled_at'],
                    'location_url' => $validated['location_url'],
                    'type' => $validated['type'],
                    'interview_status' => 'SCHEDULED'
                ]
            );

            $application = JobApplication::find($validated['job_application_id']);
            $application->update(['status' => 'INTERVIEW_SCHEDULED']);

            return response()->json([
                'message' => 'Interview scheduled successfully',
                'data' => $interview->load('jobApplication')
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'interview_status' => 'required|in:SCHEDULED,COMPLETED,CANCELLED,RESCHEDULED',
            'feedback' => 'nullable|string'
        ]);

        $interview = InterviewSchedule::findOrFail($id);
        
        $interview->update([
            'interview_status' => $request->interview_status,
            'feedback' => $request->feedback
        ]);

        if ($request->filled('feedback')) {
            $interview->jobApplication->update(['employer_notes' => $request->feedback]);
        }

        if ($request->interview_status === 'COMPLETED') {
            $interview->jobApplication->update(['status' => 'INTERVIEWED']);
        } 
        elseif ($request->interview_status === 'CANCELLED') {
            $interview->jobApplication->update(['status' => 'SHORTLISTED']);
        }

        return response()->json([
            'message' => 'Interview record updated successfully', 
            'data' => $interview->load('jobApplication')
        ]);
    }
    
    public function getForSeeker($seekerId)
    {
        $interviews = InterviewSchedule::whereHas('jobApplication', function ($query) use ($seekerId) {
            $query->where('job_seeker_id', $seekerId);
        })
        ->with(['jobApplication.jobPost.employer'])
        ->orderBy('scheduled_at', 'asc')
        ->get();

        return response()->json([
            'data' => $interviews
        ]);
    }

    public function getByApplication($applicationId)
    {
        $interviews = InterviewSchedule::where('job_application_id', $applicationId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $interviews
        ]);
    }
}