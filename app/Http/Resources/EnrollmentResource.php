<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'classroom_id' => $this->classroom_id,
            'status'       => $this->status,
            'progress'     => $this->progress,
            'enrolled_at'  => $this->enrolled_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'student'      => $this->whenLoaded('student', fn() => [
                'id'    => $this->student->id,
                'name'  => $this->student->name,
                'email' => $this->student->email,
            ]),
            'classroom'  => new ClassroomResource($this->whenLoaded('classroom')),
            'last_slide' => new SlideResource($this->whenLoaded('lastSlide')),
        ];
    }
}