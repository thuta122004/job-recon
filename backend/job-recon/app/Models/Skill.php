<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name', 'desc', 'status'];

    public function jobSeekers()
    {
        return $this->belongsToMany(JobSeekerProfile::class, 'job_seeker_skill', 'skill_id', 'job_seeker_profile_id')
                    ->withTimestamps();
    }

    public function jobPosts()
    {
        return $this->belongsToMany(JobPost::class, 'job_post_skills')
                    ->withPivot('is_required')
                    ->withTimestamps();
    }
}
