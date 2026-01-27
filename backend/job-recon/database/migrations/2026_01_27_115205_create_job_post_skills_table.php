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
        Schema::create('job_post_skills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_post_id')->constrained('job_posts');
            $table->foreignId('skill_id')->constrained('skills');
            $table->boolean('is_required')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_post_skills');
    }
};
