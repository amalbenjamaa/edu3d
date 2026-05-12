<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'classroom_id' => $this->classroom_id,
            'title'        => $this->title,
            'type'         => $this->type,
            'content'      => $this->content,   // array JSON → objets Three.js
            'camera'       => $this->camera,    // position caméra
            'order'        => $this->order,
            'duration'     => $this->duration,
            'classroom'    => new ClassroomResource($this->whenLoaded('classroom')),
        ];
    }
}