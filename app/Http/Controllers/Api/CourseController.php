<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    // GET /api/courses
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $request->user()->isTeacher()
            ? Course::with('classrooms')
                    ->forTeacher($request->user()->id)
                    ->latest()
                    ->get()
            : Course::with('teacher', 'classrooms')
                    ->published()
                    ->latest()
                    ->get();

        return CourseResource::collection($courses);
    }

    // POST /api/courses
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create-course');

        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'level'        => ['required', 'in:beginner,intermediate,advanced'],
            'is_published' => ['boolean'],
        ]);

        $course = Course::create([
            ...$data,
            'teacher_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Cours créé avec succès.',
            'course'  => new CourseResource($course),
        ], 201);
    }

    // GET /api/courses/{course}
    public function show(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('view-course', $course);

        $course->load('teacher', 'classrooms.slides');

        return response()->json([
            'course' => new CourseResource($course),
        ]);
    }

    // PUT /api/courses/{course}
    public function update(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update-course', $course);

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'level'        => ['sometimes', 'in:beginner,intermediate,advanced'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $course->update($data);

        return response()->json([
            'message' => 'Cours mis à jour.',
            'course'  => new CourseResource($course),
        ]);
    }

    // DELETE /api/courses/{course}
    public function destroy(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('delete-course', $course);

        Course::query()->whereKey($course->id)->delete();

        return response()->json([
            'message' => 'Cours supprimé.',
        ]);
    }
}