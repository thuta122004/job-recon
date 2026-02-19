<?php

namespace App\Models;

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
}
