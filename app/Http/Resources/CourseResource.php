<?php
// ═══════════════════════════════════════════════════════════════════════════════
// FICHIER 1 : app/Http/Resources/CourseResource.php
// ═══════════════════════════════════════════════════════════════════════════════

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'level'        => $this->level,
            'thumbnail'    => $this->thumbnail,
            'is_published' => $this->is_published,
            'teacher'      => $this->whenLoaded('teacher', fn() => [
                'id'   => $this->teacher->id,
                'name' => $this->teacher->name,
            ]),
            'classrooms'         => ClassroomResource::collection($this->whenLoaded('classrooms')),
            'classrooms_count'   => $this->whenCounted('classrooms'),
            'created_at'         => $this->created_at->toDateString(),
        ];
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// FICHIER 2 : app/Http/Resources/ClassroomResource.php
// ═══════════════════════════════════════════════════════════════════════════════

// namespace App\Http\Resources;
//
// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
//
// class ClassroomResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'          => $this->id,
//             'name'        => $this->name,
//             'description' => $this->description,
//             'capacity'    => $this->capacity,
//             'is_active'   => $this->is_active,
//             'start_date'  => $this->start_date?->toDateString(),
//             'end_date'    => $this->end_date?->toDateString(),
//             'course'      => new CourseResource($this->whenLoaded('course')),
//             'slides'      => SlideResource::collection($this->whenLoaded('slides')),
//             'slides_count'      => $this->whenCounted('slides'),
//             'enrollments_count' => $this->whenCounted('enrollments'),
//             'students'    => $this->whenLoaded('enrollments', fn() =>
//                 $this->enrollments->map(fn($e) => [
//                     'id'       => $e->student->id,
//                     'name'     => $e->student->name,
//                     'status'   => $e->status,
//                     'progress' => $e->progress,
//                 ])
//             ),
//         ];
//     }
// }


// ═══════════════════════════════════════════════════════════════════════════════
// FICHIER 3 : app/Http/Resources/SlideResource.php
// ═══════════════════════════════════════════════════════════════════════════════

// namespace App\Http\Resources;
//
// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
//
// class SlideResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'           => $this->id,
//             'classroom_id' => $this->classroom_id,
//             'title'        => $this->title,
//             'type'         => $this->type,
//             'content'      => $this->content,   // array JSON → Three.js objects
//             'camera'       => $this->camera,    // position caméra
//             'order'        => $this->order,
//             'duration'     => $this->duration,
//             'classroom'    => new ClassroomResource($this->whenLoaded('classroom')),
//         ];
//     }
// }


// ═══════════════════════════════════════════════════════════════════════════════
// FICHIER 4 : app/Http/Resources/EnrollmentResource.php
// ═══════════════════════════════════════════════════════════════════════════════

// namespace App\Http\Resources;
//
// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
//
// class EnrollmentResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'           => $this->id,
//             'status'       => $this->status,
//             'progress'     => $this->progress,
//             'enrolled_at'  => $this->enrolled_at?->toDateTimeString(),
//             'completed_at' => $this->completed_at?->toDateTimeString(),
//             'student'      => $this->whenLoaded('student', fn() => [
//                 'id'    => $this->student->id,
//                 'name'  => $this->student->name,
//                 'email' => $this->student->email,
//             ]),
//             'classroom'    => new ClassroomResource($this->whenLoaded('classroom')),
//             'last_slide'   => new SlideResource($this->whenLoaded('lastSlide')),
//         ];
//     }
// }