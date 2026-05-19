<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ClassroomController extends Controller
{
    // GET /api/classrooms
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $classrooms = match (true) {
            $user->isAdmin() => Classroom::with(['course.teacher', 'enrollments.student'])
                ->withCount(['slides', 'enrollments'])
                ->latest()
                ->get(),
            $user->isTeacher() => Classroom::with('course')
                ->withCount(['slides', 'enrollments'])
                ->whereHas('course', fn ($q) => $q->forTeacher($user->id))
                ->latest()
                ->get(),
            default => Classroom::with('course')
                ->withCount('slides')
                ->whereHas('enrollments', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get(),
        };

        return ClassroomResource::collection($classrooms);
    }

    // GET /api/courses/{course}/classrooms  (liste par cours)
    public function byCourse(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('view-course', $course);

        $classrooms = $course->classrooms()
                             ->with('slides')
                             ->active()
                             ->get();

        return ClassroomResource::collection($classrooms);
    }

    // POST /api/classrooms
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_id'   => ['required', 'exists:courses,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity'    => ['required', 'integer', 'min:1', 'max:500'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'   => ['boolean'],
        ]);

        $course = Course::findOrFail($data['course_id']);
        Gate::authorize('create-classroom', $course);

        $classroom = Classroom::create($data);

        return response()->json([
            'message'   => 'Classe créée avec succès.',
            'classroom' => new ClassroomResource($classroom->load('course')),
        ], 201);
    }

    // GET /api/classrooms/{classroom}
    public function show(Request $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('view-classroom', $classroom);

        $classroom->load('course', 'slides', 'enrollments.student');

        return response()->json([
            'classroom' => new ClassroomResource($classroom),
        ]);
    }

    // PUT /api/classrooms/{classroom}
    public function update(Request $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('manage-classroom', $classroom);

        $data = $request->validate([
            'course_id'   => ['sometimes', 'exists:courses,id'],
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity'    => ['sometimes', 'integer', 'min:1', 'max:500'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        if (isset($data['course_id'])) {
            $course = Course::findOrFail($data['course_id']);
            Gate::authorize('create-classroom', $course);
        }

        $classroom->update($data);

        return response()->json([
            'message'   => 'Classe mise à jour.',
            'classroom' => new ClassroomResource($classroom),
        ]);
    }

    // DELETE /api/classrooms/{classroom}
    public function destroy(Classroom $classroom): JsonResponse
    {
        Gate::authorize('manage-classroom', $classroom);

        Classroom::query()->whereKey($classroom->id)->delete();

        return response()->json([
            'message' => 'Classe supprimée.',
        ]);
    }
}