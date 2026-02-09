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
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employer_profile_id')->constrained('employer_profiles');
            $table->foreignId('job_category_id')->constrained('job_categories');
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('workplace_type', ['ON-SITE', 'REMOTE', 'HYBRID'])->default('ON-SITE');
            $table->string('location');
            $table->enum('employment_type', [
                'FULL-TIME', 
                'PART-TIME', 
                'CONTRACT', 
                'TEMPORARY', 
                'INTERNSHIP', 
                'VOLUNTEER'
            ]);
            $table->enum('experience_level', [
                'ENTRY-LEVEL', 
                'JUNIOR', 
                'MID-LEVEL', 
                'SENIOR', 
                'LEAD', 
                'DIRECTOR', 
                'EXECUTIVE'
            ]);
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->text('qualifications')->nullable();
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->boolean('salary_visible')->default(true);
            $table->enum('status', ['OPEN', 'CLOSED', 'ARCHIVED'])->default('OPEN');
            $table->timestamp('expires_at')->nullable();
            $table->integer('application_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
