<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employer_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->unique();
            $table->string('company_name');
            $table->string('company_logo_url')->nullable();
            $table->string('tagline')->nullable();
            $table->text('about_us')->nullable();
            $table->string('website_url')->nullable();
            $table->string('industry')->nullable();
            $table->string('headquarters_location')->nullable();
            $table->string('company_size')->nullable();
            $table->year('founded_year')->nullable();
            $table->string('specialties')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_profiles');
    }
};
