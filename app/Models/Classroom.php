<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'capacity',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /** Cours auquel appartient cette classe */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Slides de cette classe, triées par ordre */
    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('order');
    }

    /** Inscriptions à cette classe */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Étudiants inscrits à cette classe */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
                    ->withPivot(['status', 'progress', 'last_slide_id', 'enrolled_at', 'completed_at'])
                    ->withTimestamps();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function hasCapacity(): bool
    {
        return $this->enrollments()->whereIn('status', ['pending', 'active'])->count() < $this->capacity;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}