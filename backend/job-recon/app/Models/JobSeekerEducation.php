<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSeekerEducation extends Model
{
    protected $fillable = [
        'job_seeker_profile_id',
        'institution',
        'qualification_name',
        'field_of_study',
        'education_level',
        'start_year',
        'end_year',
        'description',
    ];

    public function profile()
    {
        return $this->belongsTo(JobSeekerProfile::class);
    }
}
