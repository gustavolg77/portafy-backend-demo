<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Proyecto;
use App\Support\OfficialSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = Proyecto::forUser($request->user()->id)
            ->orderByDesc('id_project')
            ->get();

        return response()->json($projects);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['imagen'] = $this->storeImage($request) ?? null;
        $profile = OfficialSchema::ensureProfile($request->user());

        $project = Proyecto::create([
            'title' => $data['titulo'],
            'description' => $data['descripcion'] ?? null,
            'repository_url' => $data['url_repositorio'] ?? null,
            'url_demo' => $data['url_demo'] ?? null,
            'image' => $data['imagen'] ?? null,
            'state' => OfficialSchema::databaseProjectState($data['estado'] ?? null),
            'id_profile' => $profile->getKey(),
            'visibility' => true,
        ]);

        $this->syncProjectSkills($project, $data['tecnologias'] ?? null);

        return response()->json($project, 201);
    }

    public function update(ProjectRequest $request, int $id): JsonResponse
    {
        $project = Proyecto::forUser($request->user()->id)->findOrFail($id);
        $data = $request->validated();

        $imagePath = $this->storeImage($request);
        if ($imagePath) {
            $data['imagen'] = $imagePath;
        }

        $project->update([
            'title' => $data['titulo'],
            'description' => $data['descripcion'] ?? null,
            'repository_url' => $data['url_repositorio'] ?? null,
            'url_demo' => $data['url_demo'] ?? null,
            'image' => $data['imagen'] ?? $project->getRawOriginal('image'),
            'state' => OfficialSchema::databaseProjectState($data['estado'] ?? $project->estado),
        ]);

        $this->syncProjectSkills($project, $data['tecnologias'] ?? null);

        return response()->json($project->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $project = Proyecto::forUser($request->user()->id)->findOrFail($id);
        $project->skills()->detach();
        $project->delete();

        return response()->json(['message' => 'Proyecto eliminado correctamente']);
    }

    private function storeImage(Request $request): ?string
    {
        $file = $request->file('imagen') ?? $request->file('cover');

        return $file ? $file->store('proyectos', 'public') : null;
    }

    private function syncProjectSkills(Proyecto $project, ?string $technologies): void
    {
        $skillIds = collect(OfficialSchema::splitTechnologies($technologies))
            ->map(fn (string $technology) => OfficialSchema::ensureSkill($technology, 'tecnica')->getKey())
            ->values()
            ->all();

        $project->skills()->sync($skillIds);
        $project->load('skills');
    }
}
