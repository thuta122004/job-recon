<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const ADMIN = 1;
    const JOB_SEEKER = 2;
    const EMPLOYER = 3;

    protected $fillable = ['name', 'desc', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
