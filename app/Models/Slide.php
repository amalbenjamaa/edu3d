<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'title',
        'type',
        'content',
        'camera',
        'order',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'content'  => 'array', // JSON → tableau PHP automatiquement
            'camera'   => 'array',
            'order'    => 'integer',
            'duration' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /** Classe à laquelle appartient cette slide */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne un exemple de contenu JSON valide pour Three.js.
     * Structure attendue dans la colonne `content` :
     *
     * [
     *   {
     *     "id": "obj_1",
     *     "type": "text3d",          // text3d | box | sphere | plane | gltf | image
     *     "text": "Bonjour monde",   // pour type text3d
     *     "url": null,               // pour type gltf ou image
     *     "position": [0, 0, 0],     // [x, y, z]
     *     "rotation": [0, 0, 0],     // [x, y, z] en radians
     *     "scale":    [1, 1, 1],
     *     "color": "#4A90E2",
     *     "opacity": 1.0,
     *     "castShadow": true
     *   }
     * ]
     *
     * Structure attendue dans la colonne `camera` :
     * {
     *   "position": [5, 5, 5],
     *   "target":   [0, 0, 0],
     *   "fov": 60
     * }
     */
    public static function exampleContent(): array
    {
        return [
            [
                'id'        => 'obj_1',
                'type'      => 'text3d',
                'text'      => 'Titre de la slide',
                'position'  => [0, 1, 0],
                'rotation'  => [0, 0, 0],
                'scale'     => [1, 1, 1],
                'color'     => '#ffffff',
                'opacity'   => 1.0,
                'castShadow'=> false,
            ],
            [
                'id'        => 'obj_2',
                'type'      => 'box',
                'position'  => [0, 0, 0],
                'rotation'  => [0, 0.5, 0],
                'scale'     => [2, 2, 2],
                'color'     => '#4A90E2',
                'opacity'   => 1.0,
                'castShadow'=> true,
            ],
        ];
    }

    public static function exampleCamera(): array
    {
        return [
            'position' => [5, 5, 5],
            'target'   => [0, 0, 0],
            'fov'      => 60,
        ];
    }
}