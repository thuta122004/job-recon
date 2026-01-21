<?php

use App\Http\Controllers\Api\JobSeekerEducationController;
use App\Http\Controllers\Api\JobSeekerExperienceController;
use App\Http\Controllers\Api\JobSeekerProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('roles', RoleController::class);
Route::apiResource('users', UserController::class);
Route::patch('users/{id}/suspend', [UserController::class, 'suspend']);
Route::apiResource('job-seeker-profiles', JobSeekerProfileController::class);
Route::get('job-seeker-experiences/{id}', [JobSeekerExperienceController::class, 'index']);
Route::apiResource('job-seeker-experiences', JobSeekerExperienceController::class)->except(['index']);
Route::get('job-seeker-educations/{id}', [JobSeekerEducationController::class, 'index']);
Route::apiResource('job-seeker-educations', JobSeekerEducationController::class)->except(['index']);