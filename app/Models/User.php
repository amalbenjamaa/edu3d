<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// Laravel Sanctum is optional; remove HasApiTokens import if package isn't installed

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Helpers de rôle ──────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // ─── Relations (Teacher) ──────────────────────────────────────────────────

    /** Cours créés par cet enseignant */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    // ─── Relations (Student) ──────────────────────────────────────────────────

    /** Inscriptions de l'étudiant */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Classes auxquelles l'étudiant est inscrit */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'enrollments')
                    ->withPivot(['status', 'progress', 'last_slide_id', 'enrolled_at', 'completed_at'])
                    ->withTimestamps();
    }
}