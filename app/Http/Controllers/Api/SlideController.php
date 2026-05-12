<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SlideResource;
use App\Models\Classroom;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SlideController extends Controller
{
    // GET /api/slides
    public function index(Request $request): AnonymousResourceCollection
    {
        $slides = Slide::with('classroom.course')
            ->whereHas('classroom.course', fn($q) => $q->forTeacher($request->user()->id))
            ->orderBy('classroom_id')
            ->orderBy('order')
            ->get();

        return SlideResource::collection($slides);
    }

    // GET /api/classrooms/{classroom}/slides
    public function byClassroom(Request $request, Classroom $classroom): AnonymousResourceCollection
    {
        Gate::authorize('view-slide', $classroom->slides()->first() ?? new Slide(['classroom_id' => $classroom->id]));

        $slides = $classroom->slides()->get(); // déjà triées par order (scope du modèle)

        return SlideResource::collection($slides);
    }

    // POST /api/slides
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:text3d,shape3d,image3d,model3d,mixed'],
            'content'      => ['required', 'array'],
            'content.*.id'       => ['required', 'string'],
            'content.*.type'     => ['required', 'in:text3d,box,sphere,plane,gltf,image'],
            'content.*.position' => ['required', 'array', 'size:3'],
            'content.*.rotation' => ['required', 'array', 'size:3'],
            'content.*.scale'    => ['required', 'array', 'size:3'],
            'content.*.color'    => ['nullable', 'string'],
            'camera'       => ['nullable', 'array'],
            'camera.position' => ['nullable', 'array', 'size:3'],
            'camera.target'   => ['nullable', 'array', 'size:3'],
            'camera.fov'      => ['nullable', 'numeric'],
            'duration'     => ['integer', 'min:0'],
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        Gate::authorize('create-slide', $classroom);

        // Calculer l'ordre automatiquement (dernière slide + 1)
        $lastOrder = $classroom->slides()->max('order') ?? 0;

        $slide = Slide::create([
            ...$data,
            'order' => $lastOrder + 1,
        ]);

        return response()->json([
            'message' => 'Slide créée avec succès.',
            'slide'   => new SlideResource($slide),
        ], 201);
    }

    // GET /api/slides/{slide}
    public function show(Request $request, Slide $slide): JsonResponse
    {
        Gate::authorize('view-slide', $slide);

        return response()->json([
            'slide' => new SlideResource($slide->load('classroom')),
        ]);
    }

    // PUT /api/slides/{slide}
    public function update(Request $request, Slide $slide): JsonResponse
    {
        Gate::authorize('manage-slide', $slide);

        $data = $request->validate([
            'title'    => ['sometimes', 'string', 'max:255'],
            'type'     => ['sometimes', 'in:text3d,shape3d,image3d,model3d,mixed'],
            'content'  => ['sometimes', 'array'],
            'camera'   => ['nullable', 'array'],
            'duration' => ['sometimes', 'integer', 'min:0'],
        ]);

        $slide->update($data);

        return response()->json([
            'message' => 'Slide mise à jour.',
            'slide'   => new SlideResource($slide),
        ]);
    }

    // DELETE /api/slides/{slide}
    public function destroy(Slide $slide): JsonResponse
    {
        Gate::authorize('manage-slide', $slide);

        $classroomId = $slide->classroom_id;
        Slide::query()->whereKey($slide->id)->delete();

        // Recalculer les ordres après suppression
        Slide::where('classroom_id', '=', $classroomId, 'and')
             ->orderBy('order')
             ->get()
             ->each(fn($s, $i) => $s->update(['order' => $i + 1]));

        return response()->json([
            'message' => 'Slide supprimée.',
        ]);
    }

    // POST /api/slides/reorder
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'slides'       => ['required', 'array'],
            'slides.*.id'  => ['required', 'exists:slides,id'],
            'slides.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        Gate::authorize('create-slide', $classroom); // même gate : enseignant propriétaire

        DB::transaction(function () use ($data) {
            foreach ($data['slides'] as $item) {
                Slide::where('id', '=', $item['id'], 'and')
                     ->update(['order' => $item['order']]);
            }
        });

        return response()->json([
            'message' => 'Ordre des slides mis à jour.',
            'slides'  => SlideResource::collection(
                $classroom->slides()->get()
            ),
        ]);
    }
}