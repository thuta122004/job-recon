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
        Schema::create('job_seeker_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_seeker_profile_id')->constrained('job_seeker_profiles');

            $table->string('institution');
            $table->string('qualification_name');
            $table->string('field_of_study')->nullable();
            $table->enum('education_level', ['CERTIFICATE', 'DIPLOMA', 'BACHELOR', 'MASTER', 'PHD']);
            $table->year('start_year');
            $table->year('end_year')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_seeker_education');
    }
};
