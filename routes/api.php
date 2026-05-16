<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\SlideController;
use Illuminate\Support\Facades\Route;

// ─── Routes publiques ─────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── Routes protégées ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/me',      [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Admin ─────────────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users',         fn() => \App\Models\User::all());
        Route::put('/users/{user}',  fn(\App\Models\User $user, \Illuminate\Http\Request $req) =>
            $user->update($req->only('role', 'name')) ? response()->json(['user' => $user]) : abort(500)
        );
        Route::delete('/users/{user}', fn(\App\Models\User $user) =>
            $user->delete($user->id) ? response()->json(['message' => 'Utilisateur supprimé.']) : abort(500)
        );
    });

    // ── Lecture : tous les rôles authentifiés (gates dans les contrôleurs) ─
    Route::middleware('role:student,teacher,admin')->group(function () {
        Route::get('courses',    [CourseController::class,    'index']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
        Route::get('classrooms', [ClassroomController::class, 'index']);
        Route::get('classrooms/{classroom}', [ClassroomController::class, 'show']);
        Route::get('classrooms/{classroom}/slides', [SlideController::class, 'byClassroom']);
        Route::get('courses/{course}/classrooms', [ClassroomController::class, 'byCourse']);
        Route::get('slides/{slide}', [SlideController::class, 'show']);

        Route::apiResource('enrollments', EnrollmentController::class)
             ->only(['index', 'store', 'show']);
        Route::put('enrollments/{enrollment}/progress', [EnrollmentController::class, 'updateProgress']);
    });

    // ── Écriture : enseignant + admin ─────────────────────────────────────
    Route::middleware('role:teacher,admin')->group(function () {
        Route::post('courses',    [CourseController::class,    'store']);
        Route::put('courses/{course}', [CourseController::class, 'update']);
        Route::delete('courses/{course}', [CourseController::class, 'destroy']);

        Route::post('classrooms', [ClassroomController::class, 'store']);
        Route::put('classrooms/{classroom}', [ClassroomController::class, 'update']);
        Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy']);

        Route::get('slides', [SlideController::class, 'index']);
        Route::post('slides', [SlideController::class, 'store']);
        Route::put('slides/{slide}', [SlideController::class, 'update']);
        Route::delete('slides/{slide}', [SlideController::class, 'destroy']);
        Route::post('slides/reorder', [SlideController::class, 'reorder']);
    });
});