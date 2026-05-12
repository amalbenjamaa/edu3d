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

    // ── Enseignant + Admin ────────────────────────────────────────────────
    Route::middleware('role:teacher,admin')->group(function () {
        Route::apiResource('courses',    CourseController::class);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('slides',     SlideController::class);
        Route::post('slides/reorder',    [SlideController::class, 'reorder']);
    });

    // ── Tous les rôles authentifiés ───────────────────────────────────────
    Route::middleware('role:student,teacher,admin')->group(function () {
        // Slides d'une classe (étudiant inscrit peut consulter)
        Route::get('classrooms/{classroom}/slides', [SlideController::class,    'byClassroom']);
        // Classes d'un cours
        Route::get('courses/{course}/classrooms',   [ClassroomController::class, 'byCourse']);
        // Inscriptions
        Route::apiResource('enrollments', EnrollmentController::class)
             ->only(['index', 'store', 'show']);
        Route::put('enrollments/{enrollment}/progress', [EnrollmentController::class, 'updateProgress']);
    });
});