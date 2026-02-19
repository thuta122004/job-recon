<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSeekerProfile extends Model
{
    protected $fillable = ['user_id', 'profile_picture_url', 'headline', 'summary', 'location', 'current_position', 'experience_years', 'resume_url', 'profile_visibility'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    protected function profilePictureUrl(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? asset('storage/' . $value) : null);
    }

    protected function resumeUrl(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? asset('storage/' . $value) : null);
    }

    public function experiences()
    {
        return $this->hasMany(JobSeekerExperience::class);
    }

    public function educations()
    {
        return $this->hasMany(JobSeekerEducation::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_seeker_skills', 'job_seeker_profile_id', 'skill_id')
                    ->withPivot('proficiency')
                    ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_seeker_id');
    }
}
