<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSchedule extends Model
{
    protected $fillable = [
        'job_application_id',
        'title',
        'scheduled_at',
        'location_url',
        'type',
        'interview_status',
        'feedback',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
