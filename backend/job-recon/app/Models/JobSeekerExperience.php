<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSeekerExperience extends Model
{
    protected $fillable = [
        'job_seeker_profile_id',
        'job_title',
        'company_name',
        'location',
        'employment_type',
        'start_date',
        'end_date',
        'description'
    ];

    public function profile()
    {
        return $this->belongsTo(JobSeekerProfile::class);
    }
}
