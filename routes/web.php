<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Auth/Login'));
Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
Route::get('/register', fn() => Inertia::render('Auth/Register'))->name('register');

// Routes protégées (après auth)
Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', fn() => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');
    Route::get('/teacher/dashboard', fn() => Inertia::render('Teacher/Dashboard'))->name('teacher.dashboard');
    Route::get('/student/dashboard', fn() => Inertia::render('Student/Dashboard'))->name('student.dashboard');
});