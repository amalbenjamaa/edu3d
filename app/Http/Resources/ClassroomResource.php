<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'course_id'   => $this->course_id,
            'name'        => $this->name,
            'description' => $this->description,
            'capacity'    => $this->capacity,
            'is_active'   => $this->is_active,
            'invite_code' => $this->invite_code,
            'start_date'  => $this->start_date?->toDateString(),
            'end_date'    => $this->end_date?->toDateString(),
            'course'      => new CourseResource($this->whenLoaded('course')),
            'slides'      => SlideResource::collection($this->whenLoaded('slides')),
            'slides_count'      => $this->whenCounted('slides'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'students'    => $this->whenLoaded('enrollments', fn() =>
                $this->enrollments->map(fn($e) => [
                    'id'       => $e->student->id,
                    'name'     => $e->student->name,
                    'status'   => $e->status,
                    'progress' => $e->progress,
                ])
            ),
        ];
    }
}