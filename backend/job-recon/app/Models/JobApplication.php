<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    protected $fillable = [
        'job_post_id',
        'job_seeker_id',
        'cover_letter',
        'status',
        'last_status_change',
        'employer_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'last_status_change' => 'datetime',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    public function jobSeeker(): BelongsTo
    {
        return $this->belongsTo(JobSeekerProfile::class, 'job_seeker_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(InterviewSchedule::class);
    }
}
