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
        $query = Slide::with('classroom.course')->orderBy('classroom_id')->orderBy('order');

        if ($request->user()->isTeacher()) {
            $query->whereHas('classroom.course', fn ($q) => $q->forTeacher($request->user()->id));
        }

        $slides = $query->get();

        return SlideResource::collection($slides);
    }

    // GET /api/classrooms/{classroom}/slides
    public function byClassroom(Request $request, Classroom $classroom): AnonymousResourceCollection
    {
        $classroom->load('course');
        Gate::authorize('view-classroom', $classroom);

        $slides = $classroom->slides()->get();

        return SlideResource::collection($slides);
    }

    // POST /api/slides
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:text3d,shape3d,image3d,model3d,mixed'],
            'content'      => ['nullable', 'array'],
            'content.*.id'       => ['required_with:content', 'string'],
            'content.*.type'     => ['required_with:content', 'in:text3d,box,sphere,plane,cylinder,torus,cone,gltf,image'],
            'content.*.position' => ['required_with:content', 'array', 'size:3'],
            'content.*.rotation' => ['required_with:content', 'array', 'size:3'],
            'content.*.scale'    => ['required_with:content', 'array', 'size:3'],
            'content.*.color'    => ['nullable', 'string'],
            'content.*.text'     => ['nullable', 'string'],
            'content.*.url'      => ['nullable', 'string'],
            'content.*.opacity'  => ['nullable', 'numeric'],
            'content.*.castShadow'=> ['nullable', 'boolean'],
            'camera'       => ['nullable', 'array'],
            'camera.position' => ['nullable', 'array', 'size:3'],
            'camera.target'   => ['nullable', 'array', 'size:3'],
            'camera.fov'      => ['nullable', 'numeric'],
            'duration'     => ['nullable', 'integer', 'min:0'],
        ]);

        $classroom = Classroom::findOrFail($data['classroom_id']);
        Gate::authorize('create-slide', $classroom);

        $data['content']  = $data['content'] ?? [];
        $data['duration'] = $data['duration'] ?? 0;

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
            'content.*.id'       => ['required_with:content', 'string'],
            'content.*.type'     => ['required_with:content', 'in:text3d,box,sphere,plane,cylinder,torus,cone,gltf,image'],
            'content.*.position' => ['required_with:content', 'array', 'size:3'],
            'content.*.rotation' => ['required_with:content', 'array', 'size:3'],
            'content.*.scale'    => ['required_with:content', 'array', 'size:3'],
            'content.*.color'    => ['nullable', 'string'],
            'content.*.text'     => ['nullable', 'string'],
            'content.*.url'      => ['nullable', 'string'],
            'content.*.opacity'  => ['nullable', 'numeric'],
            'content.*.castShadow'=> ['nullable', 'boolean'],
            'camera'   => ['nullable', 'array'],
            'camera.position' => ['nullable', 'array', 'size:3'],
            'camera.target'   => ['nullable', 'array', 'size:3'],
            'camera.fov'      => ['nullable', 'numeric'],
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
        Slide::where('classroom_id', $classroomId)
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
                Slide::where('id', $item['id'])
                     ->where('classroom_id', $data['classroom_id'])
                     ->update(['order' => $item['order']]);
            }
        });

        return response()->json([
            'message' => 'Ordre des slides mis à jour.',
            'slides'  => SlideResource::collection(
                Slide::where('classroom_id', $data['classroom_id'])->orderBy('order')->get()
            ),
        ]);
    }
}