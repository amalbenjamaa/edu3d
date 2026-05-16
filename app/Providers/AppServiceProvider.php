<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            $user = $request->user();
            if (! $user) {
                return '/';
            }

            return match ($user->role) {
                'admin' => route('admin.dashboard'),
                'teacher' => route('teacher.dashboard'),
                'student' => route('student.dashboard'),
                default => route('login'),
            };
        });

        $this->defineGates();
    }

    // ─── Gates ────────────────────────────────────────────────────────────────

    private function defineGates(): void
    {
        // ── Admin : accès total ────────────────────────────────────────────

        // L'admin passe toutes les gates automatiquement
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true; // court-circuit : admin peut tout faire
            }
        });

        // ── Courses ───────────────────────────────────────────────────────

        // Créer un cours : enseignants seulement
        Gate::define('create-course', function (User $user) {
            return $user->isTeacher();
        });

        // Modifier/supprimer un cours : enseignant propriétaire seulement
        Gate::define('update-course', function (User $user, Course $course) {
            return $user->isTeacher() && $user->id === $course->teacher_id;
        });

        Gate::define('delete-course', function (User $user, Course $course) {
            return $user->isTeacher() && $user->id === $course->teacher_id;
        });

        // Voir un cours : tout le monde (publié) ou l'enseignant propriétaire
        Gate::define('view-course', function (User $user, Course $course) {
            return $course->is_published || $user->id === $course->teacher_id;
        });

        // ── Classrooms ────────────────────────────────────────────────────

        // Créer une classe : enseignant propriétaire du cours
        Gate::define('create-classroom', function (User $user, Course $course) {
            return $user->isTeacher() && $user->id === $course->teacher_id;
        });

        // Modifier/supprimer une classe : enseignant propriétaire du cours parent
        Gate::define('manage-classroom', function (User $user, Classroom $classroom) {
            $classroom->loadMissing('course');

            return $user->isTeacher() && $user->id === $classroom->course->teacher_id;
        });

        Gate::define('view-classroom', function (User $user, Classroom $classroom) {
            $classroom->loadMissing('course');

            if ($user->isTeacher()) {
                return $user->id === $classroom->course->teacher_id;
            }

            if ($user->isStudent()) {
                return Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('classroom_id', $classroom->id)
                    ->whereIn('status', ['pending', 'active', 'completed'])
                    ->exists();
            }

            return false;
        });

        // ── Slides ────────────────────────────────────────────────────────

        // Créer/modifier/supprimer une slide : enseignant propriétaire de la classe
        Gate::define('manage-slide', function (User $user, Slide $slide) {
            $slide->loadMissing('classroom.course');

            return $user->isTeacher()
                && $user->id === $slide->classroom->course->teacher_id;
        });

        // Créer une slide dans une classe : enseignant propriétaire
        Gate::define('create-slide', function (User $user, Classroom $classroom) {
            $classroom->loadMissing('course');

            return $user->isTeacher()
                && $user->id === $classroom->course->teacher_id;
        });

        // Voir les slides : étudiant inscrit à la classe OU enseignant propriétaire
        Gate::define('view-slide', function (User $user, Slide $slide) {
            $slide->loadMissing('classroom.course');

            if ($user->isTeacher()) {
                return $user->id === $slide->classroom->course->teacher_id;
            }

            if ($user->isStudent()) {
                return Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('classroom_id', $slide->classroom_id)
                    ->whereIn('status', ['pending', 'active', 'completed'])
                    ->exists();
            }

            return false;
        });

        // ── Enrollments ───────────────────────────────────────────────────

        // S'inscrire à une classe : étudiants seulement
        Gate::define('enroll', function (User $user, Classroom $classroom) {
            return $user->isStudent() && $classroom->hasCapacity();
        });

        // Voir les inscriptions d'une classe : enseignant propriétaire
        Gate::define('view-enrollments', function (User $user, Classroom $classroom) {
            return $user->isTeacher()
                && $user->id === $classroom->course->teacher_id;
        });

        // ── Users (admin) ─────────────────────────────────────────────────

        // Gérer les utilisateurs : admin seulement (déjà géré par Gate::before)
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });
    }
}