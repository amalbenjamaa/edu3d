<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    // GET /api/enrollments
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $enrollments = $user->isTeacher()
            // Enseignant : toutes les inscriptions de ses classes
            ? Enrollment::with('student', 'classroom.course', 'lastSlide')
                        ->whereHas('classroom.course', fn($q) => $q->forTeacher($user->id))
                        ->latest()
                        ->get()
            // Étudiant : ses propres inscriptions
            : Enrollment::with('classroom.course', 'lastSlide')
                        ->forStudent($user->id)
                        ->latest()
                        ->get();

        return EnrollmentResource::collection($enrollments);
    }

    // POST /api/enrollments  — étudiant s'inscrit à une classe
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        Gate::authorize('enroll', $classroom);

        // Vérifier qu'il n'est pas déjà inscrit
        $existing = Enrollment::query()
                       ->where('user_id', '=', $request->user()->id)
                       ->where('classroom_id', '=', $classroom->id)
                               ->first();

        if ($existing) {
            return response()->json([
                'message'    => 'Vous êtes déjà inscrit à cette classe.',
                'enrollment' => new EnrollmentResource($existing),
            ], 409);
        }

        $enrollment = Enrollment::create([
            'user_id'      => $request->user()->id,
            'classroom_id' => $classroom->id,
            'status'       => 'active',
            'progress'     => 0,
            'enrolled_at'  => now(),
        ]);

        return response()->json([
            'message'    => 'Inscription réussie.',
            'enrollment' => new EnrollmentResource($enrollment->load('classroom.course')),
        ], 201);
    }

    // GET /api/enrollments/{enrollment}
    public function show(Request $request, Enrollment $enrollment): JsonResponse
    {
        // Étudiant ne peut voir que sa propre inscription
        if ($request->user()->isStudent() && $enrollment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $enrollment->load('student', 'classroom.course', 'classroom.slides', 'lastSlide');

        return response()->json([
            'enrollment' => new EnrollmentResource($enrollment),
        ]);
    }

    // PUT /api/enrollments/{enrollment}/progress  — mettre à jour la progression
    public function updateProgress(Request $request, Enrollment $enrollment): JsonResponse
    {
        // Seul l'étudiant concerné peut mettre à jour sa progression
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $data = $request->validate([
            'slide_id' => ['required', 'exists:slides,id'],
        ]);

        $slide = Slide::findOrFail($data['slide_id']);

        // Vérifier que la slide appartient bien à la classe de l'inscription
        if ($slide->classroom_id !== $enrollment->classroom_id) {
            return response()->json([
                'message' => 'Cette slide n\'appartient pas à ta classe.',
            ], 422);
        }

        $enrollment->updateProgress($slide);

        return response()->json([
            'message'    => 'Progression mise à jour.',
            'enrollment' => new EnrollmentResource($enrollment->fresh('lastSlide')),
        ]);
    }
}