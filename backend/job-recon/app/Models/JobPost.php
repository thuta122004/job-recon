<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    protected $fillable = [
        'employer_profile_id', 'job_category_id', 'title', 'slug',
        'workplace_type', 'location', 'employment_type', 'experience_level',
        'description', 'responsibilities', 'qualifications',
        'salary_min', 'salary_max', 'currency', 'salary_visible',
        'status', 'expires_at', 'application_count'
    ];

    public function employer()
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_post_skills')
                    ->withPivot('is_required')
                    ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedBy()
    {
        return $this->belongsToMany(JobSeekerProfile::class, 'saved_jobs', 'job_post_id', 'job_seeker_profile_id')
                    ->withTimestamps();
    }

    protected static function booted()
    {
        static::retrieved(function ($job) {
            if ($job->getRawOriginal('status') === 'OPEN' && 
                $job->expires_at && 
                Carbon::parse($job->expires_at)->isPast()) {
                
                $job->update(['status' => 'CLOSED']);
            }
        });
    }
}
