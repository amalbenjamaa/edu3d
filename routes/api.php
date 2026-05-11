<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Api\EnrollmentController;
use Illuminate\Support\Facades\Route;

// ─── Routes publiques (sans authentification) ─────────────────────────────────

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── Routes protégées (token Sanctum requis) ──────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Profil de l'utilisateur connecté (tous les rôles)
    Route::get('/me',  [AuthController::class, 'me']);
    Route::put('/me',  [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Admin uniquement ──────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', fn() => \App\Models\User::all());
        // → à remplacer par un UserController complet à l'étape suivante
    });

    // ── Enseignant uniquement ─────────────────────────────────────────────
    Route::middleware('role:teacher,admin')->group(function () {
        Route::apiResource('courses',    \App\Http\Controllers\Api\CourseController::class);
        Route::apiResource('classrooms', \App\Http\Controllers\Api\ClassroomController::class);
        Route::apiResource('slides',     \App\Http\Controllers\Api\SlideController::class);
        Route::post('slides/reorder',    [\App\Http\Controllers\Api\SlideController::class, 'reorder']);
    });

    // ── Étudiant + Enseignant + Admin ─────────────────────────────────────
    Route::middleware('role:student,teacher,admin')->group(function () {
        Route::get('courses/{course}/classrooms', [\App\Http\Controllers\Api\ClassroomController::class, 'byCourse']);
        Route::get('classrooms/{classroom}/slides', [\App\Http\Controllers\Api\SlideController::class, 'byClassroom']);
        Route::apiResource('enrollments', \App\Http\Controllers\Api\EnrollmentController::class)
             ->only(['index', 'store', 'show']);
        Route::put('enrollments/{enrollment}/progress', [\App\Http\Controllers\Api\EnrollmentController::class, 'updateProgress']);
    });
});