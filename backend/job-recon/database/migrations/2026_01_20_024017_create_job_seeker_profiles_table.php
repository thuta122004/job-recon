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
        Schema::create('job_seeker_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->unique();
            $table->string('profile_picture_url')->nullable();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->string('location')->nullable();
            $table->string('current_position')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('resume_url')->nullable();
            $table->enum('profile_visibility', ['PUBLIC', 'PRIVATE'])->default('PUBLIC');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_seeker_profiles');
    }
};
