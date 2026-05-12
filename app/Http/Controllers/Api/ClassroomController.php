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
        $classrooms = $request->user()->isTeacher()
            ? Classroom::with('course')
                       ->whereHas('course', fn($q) => $q->forTeacher($request->user()->id))
                       ->latest()
                       ->get()
            : Classroom::with('course')
                       ->whereHas('enrollments', fn($q) => $q->where('user_id', $request->user()->id))
                       ->latest()
                       ->get();

        return ClassroomResource::collection($classrooms);
    }

    // GET /api/courses/{course}/classrooms  (liste par cours)
    public function byCourse(Course $course): AnonymousResourceCollection
    {
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
        Gate::authorize('manage-classroom', $classroom);

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
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity'    => ['sometimes', 'integer', 'min:1', 'max:500'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

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