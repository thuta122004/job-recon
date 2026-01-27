<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class EmployerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'company_logo_url',
        'tagline',
        'about_us',
        'website_url',
        'industry',
        'headquarters_location',
        'company_size',
        'founded_year',
        'specialties',
        'linkedin_url',
        'is_verified',
    ];

    protected $casts = [
        'specialties' => 'array',
        'is_verified' => 'boolean',
        'founded_year' => 'integer',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    protected function companyLogoUrl(): Attribute
    {
        return Attribute::get(
            fn ($value) => $value ? asset('storage/' . $value) : null
        );
    }
}
