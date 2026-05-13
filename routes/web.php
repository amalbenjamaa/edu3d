<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Authentification ─────────────────────────────────────────────────────────
Route::get('/login',  fn() => Inertia::render('Login'))->name('login');
Route::get('/logout', fn() => redirect('/login'));

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard',  fn() => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
    Route::get('/users',      fn() => Inertia::render('Admin/Users'))->name('admin.users');
    Route::get('/courses',    fn() => Inertia::render('Admin/Courses'))->name('admin.courses');
    Route::get('/classrooms', fn() => Inertia::render('Admin/Classrooms'))->name('admin.classrooms');
});

// ─── Enseignant ───────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('teacher')->group(function () {
    Route::get('/dashboard',          fn() => Inertia::render('Teacher/Dashboard'))->name('teacher.dashboard');
    Route::get('/courses',            fn() => Inertia::render('Teacher/Courses'))->name('teacher.courses');
    Route::get('/courses/create',     fn() => Inertia::render('Teacher/CourseForm'))->name('teacher.courses.create');
    Route::get('/courses/{id}',       fn($id) => Inertia::render('Teacher/CourseDetail', ['courseId' => $id]))->name('teacher.courses.show');
    Route::get('/classrooms',         fn() => Inertia::render('Teacher/Classrooms'))->name('teacher.classrooms');
    Route::get('/classrooms/{id}',    fn($id) => Inertia::render('Teacher/ClassroomDetail', ['classroomId' => $id]))->name('teacher.classrooms.show');
    Route::get('/slides',             fn() => Inertia::render('Teacher/Slides'))->name('teacher.slides');
    Route::get('/students',           fn() => Inertia::render('Teacher/Students'))->name('teacher.students');
});

// ─── Étudiant ─────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:student,teacher,admin'])->prefix('student')->group(function () {
    Route::get('/dashboard',                       fn() => Inertia::render('Student/Dashboard'))->name('student.dashboard');
    Route::get('/courses',                         fn() => Inertia::render('Student/Courses'))->name('student.courses');
    Route::get('/my-courses',                      fn() => Inertia::render('Student/MyCourses'))->name('student.my-courses');
    Route::get('/classrooms/{id}',                 fn($id) => Inertia::render('Student/ClassroomView', ['classroomId' => $id]))->name('student.classrooms.show');
    Route::get('/classrooms/{cid}/slides/{sid}',   fn($cid, $sid) => Inertia::render('Student/SlideView', ['classroomId' => $cid, 'slideId' => $sid]))->name('student.slides.show');
});

// ─── Fallback → login ─────────────────────────────────────────────────────────
Route::get('/', fn() => redirect('/login'));