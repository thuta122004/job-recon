<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_class',
        'status',
    ];

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'job_category_id');
    }
}
