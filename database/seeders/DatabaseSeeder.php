<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Créer les utilisateurs de test ──────────────────────────────

        $admin = User::create([
            'name'     => 'Admin edu3d',
            'email'    => 'admin@edu3d.test',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        $teacher = User::create([
            'name'     => 'Prof. Dupont',
            'email'    => 'teacher@edu3d.test',
            'password' => Hash::make('password'),
            'role'     => 'teacher',
        ]);

        $student1 = User::create([
            'name'     => 'Alice Martin',
            'email'    => 'alice@edu3d.test',
            'password' => Hash::make('password'),
            'role'     => 'student',
        ]);

        $student2 = User::create([
            'name'     => 'Bob Leroy',
            'email'    => 'bob@edu3d.test',
            'password' => Hash::make('password'),
            'role'     => 'student',
        ]);

        // ── 2. Créer un cours ──────────────────────────────────────────────

        $course = Course::create([
            'teacher_id'   => $teacher->id,
            'title'        => 'Introduction à la géométrie 3D',
            'description'  => 'Cours interactif en 3D pour apprendre les bases de la géométrie dans l\'espace.',
            'level'        => 'beginner',
            'is_published' => true,
        ]);

        // ── 3. Créer une classe ────────────────────────────────────────────

        $classroom = Classroom::create([
            'course_id'   => $course->id,
            'name'        => 'Groupe A — Printemps 2026',
            'description' => 'Première session du cours.',
            'capacity'    => 25,
            'start_date'  => '2026-02-01',
            'end_date'    => '2026-06-30',
            'is_active'   => true,
        ]);

        // ── 4. Créer des slides ────────────────────────────────────────────

        Slide::create([
            'classroom_id' => $classroom->id,
            'title'        => 'Introduction — Le cube',
            'type'         => 'mixed',
            'order'        => 1,
            'duration'     => 0,
            'content'      => [
                [
                    'id'        => 'title',
                    'type'      => 'text3d',
                    'text'      => 'Le Cube',
                    'position'  => [0, 2.5, 0],
                    'rotation'  => [0, 0, 0],
                    'scale'     => [0.5, 0.5, 0.5],
                    'color'     => '#ffffff',
                    'opacity'   => 1.0,
                    'castShadow'=> false,
                ],
                [
                    'id'        => 'cube',
                    'type'      => 'box',
                    'position'  => [0, 0, 0],
                    'rotation'  => [0.3, 0.5, 0],
                    'scale'     => [2, 2, 2],
                    'color'     => '#4A90E2',
                    'opacity'   => 1.0,
                    'castShadow'=> true,
                ],
            ],
            'camera' => [
                'position' => [5, 5, 5],
                'target'   => [0, 0, 0],
                'fov'      => 60,
            ],
        ]);

        Slide::create([
            'classroom_id' => $classroom->id,
            'title'        => 'La sphère et ses propriétés',
            'type'         => 'shape3d',
            'order'        => 2,
            'duration'     => 0,
            'content'      => [
                [
                    'id'        => 'sphere',
                    'type'      => 'sphere',
                    'position'  => [0, 0, 0],
                    'rotation'  => [0, 0, 0],
                    'scale'     => [1.5, 1.5, 1.5],
                    'color'     => '#E24B4A',
                    'opacity'   => 0.85,
                    'castShadow'=> true,
                ],
                [
                    'id'        => 'label',
                    'type'      => 'text3d',
                    'text'      => 'Sphère : r = 1.5',
                    'position'  => [0, -2.5, 0],
                    'rotation'  => [0, 0, 0],
                    'scale'     => [0.4, 0.4, 0.4],
                    'color'     => '#ffffff',
                    'opacity'   => 1.0,
                    'castShadow'=> false,
                ],
            ],
            'camera' => [
                'position' => [0, 3, 6],
                'target'   => [0, 0, 0],
                'fov'      => 55,
            ],
        ]);

        // ── 5. Inscrire les étudiants ──────────────────────────────────────

        Enrollment::create([
            'user_id'      => $student1->id,
            'classroom_id' => $classroom->id,
            'status'       => 'active',
            'progress'     => 50,
            'enrolled_at'  => now()->subDays(10),
        ]);

        Enrollment::create([
            'user_id'      => $student2->id,
            'classroom_id' => $classroom->id,
            'status'       => 'pending',
            'progress'     => 0,
            'enrolled_at'  => now()->subDay(),
        ]);

        $this->command->info('✅ Seeder terminé :');
        $this->command->info('   admin@edu3d.test   / password');
        $this->command->info('   teacher@edu3d.test / password');
        $this->command->info('   alice@edu3d.test   / password');
        $this->command->info('   bob@edu3d.test     / password');
    }
}