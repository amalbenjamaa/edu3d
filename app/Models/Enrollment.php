<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'classroom_id',
        'status',
        'progress',
        'last_slide_id',
        'enrolled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress'     => 'integer',
            'enrolled_at'  => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /** L'étudiant inscrit */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** La classe concernée */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** Dernière slide consultée */
    public function lastSlide(): BelongsTo
    {
        return $this->belongsTo(Slide::class, 'last_slide_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Mettre à jour la progression à partir du numéro de slide actuelle.
     */
    public function updateProgress(Slide $slide): void
    {
        $totalSlides = $this->classroom->slides()->count();

        if ($totalSlides === 0) {
            return;
        }

        $progress = (int) round(($slide->order / $totalSlides) * 100);

        $this->update([
            'last_slide_id' => $slide->id,
            'progress'      => min($progress, 100),
            'status'        => $progress >= 100 ? 'completed' : 'active',
            'completed_at'  => $progress >= 100 ? now() : null,
        ]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForStudent($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}