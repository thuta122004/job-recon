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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_post_id')->constrained('job_posts')->onDelete('cascade');
            $table->foreignId('job_seeker_id')->constrained('job_seeker_profiles')->onDelete('cascade');
            $table->text('cover_letter')->nullable();
            $table->enum('status', [
                'PENDING', 
                'REVIEWING', 
                'SHORTLISTED', 
                'INTERVIEW_SCHEDULED', 
                'INTERVIEWED',
                'OFFERED', 
                'REJECTED', 
                'WITHDRAWN'
            ])->default('PENDING');
            $table->timestamp('last_status_change')->nullable();
            $table->text('employer_notes')->nullable(); 
            $table->text('rejection_reason')->nullable();
            $table->unique(['job_post_id', 'job_seeker_id'], 'unique_application');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
