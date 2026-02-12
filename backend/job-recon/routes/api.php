<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployerProfileController;
use App\Http\Controllers\Api\JobCategoryController;
use App\Http\Controllers\Api\JobPostController;
use App\Http\Controllers\Api\JobSeekerEducationController;
use App\Http\Controllers\Api\JobSeekerExperienceController;
use App\Http\Controllers\Api\JobSeekerProfileController;
use App\Http\Controllers\Api\JobSeekerSkillController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::patch('users/{id}/suspend', [UserController::class, 'suspend']);
    Route::apiResource('job-seeker-profiles', JobSeekerProfileController::class);
    Route::get('job-seeker-experiences/{id}', [JobSeekerExperienceController::class, 'index']);
    Route::apiResource('job-seeker-experiences', JobSeekerExperienceController::class)->except(['index']);
    Route::get('job-seeker-educations/{id}', [JobSeekerEducationController::class, 'index']);
    Route::apiResource('job-seeker-educations', JobSeekerEducationController::class)->except(['index']);
    Route::apiResource('skills', SkillController::class);
    Route::get('job-seeker/skills/{id}', [JobSeekerSkillController::class, 'index']);
    Route::post('job-seeker/skills', [JobSeekerSkillController::class, 'store']);
    Route::delete('job-seeker/skills/{profileId}/{skillId}', [JobSeekerSkillController::class, 'destroy']);
    Route::apiResource('job-categories', JobCategoryController::class);
    Route::apiResource('employer-profiles', EmployerProfileController::class);
    Route::get('employer-profiles/{id}/job-posts', [JobPostController::class, 'getByEmployer']);
    Route::apiResource('job-posts', JobPostController::class)->except(['index']);
    Route::patch('job-posts/{id}/toggle-visibility', [JobPostController::class, 'toggleVisibility']);
    Route::patch('job-posts/{id}/toggle-salary', [JobPostController::class, 'toggleSalaryVisibility']);
    Route::post('/job-posts/{id}/restore', [JobPostController::class, 'restore']);
    Route::get('/admin/dashboard-stats', [DashboardController::class, 'adminDashboard']);
});

Route::get('/seeker/home-data', [JobSeekerProfileController::class, 'getHomeData']);
Route::get('/seeker/jobs/{slug}', [JobPostController::class, 'detail']);
Route::get('/seeker/jobs', [JobPostController::class, 'show']);
Route::get('/seeker/my-profile/{id}', [JobSeekerProfileController::class, 'show']);

Route::get('/employer/home-data/{id}', [EmployerProfileController::class, 'getHomeData']);


