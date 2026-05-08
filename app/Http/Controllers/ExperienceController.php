<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExperienceRequest;
use App\Models\Experience;
use App\Support\OfficialSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Experience::forUser($request->user()->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id_experience')
            ->get();

        return response()->json($items);
    }

    public function store(ExperienceRequest $request): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());

        $experience = Experience::create([
            'type' => $this->databaseType($request->input('tipo')),
            'company' => $request->input('empresa'),
            'title' => $request->input('cargo'),
            'description' => $request->input('descripcion'),
            'start_date' => $request->input('fecha_inicio'),
            'end_date' => $request->input('fecha_fin'),
            'state' => 'public',
            'id_profile' => $profile->getKey(),
        ]);

        return response()->json($experience, 201);
    }

    public function update(ExperienceRequest $request, int $id): JsonResponse
    {
        $experience = Experience::forUser($request->user()->id)->findOrFail($id);
        $experience->update([
            'type' => $this->databaseType($request->input('tipo')),
            'company' => $request->input('empresa'),
            'title' => $request->input('cargo'),
            'description' => $request->input('descripcion'),
            'start_date' => $request->input('fecha_inicio'),
            'end_date' => $request->input('fecha_fin'),
            'state' => 'public',
        ]);

        return response()->json($experience->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $experience = Experience::forUser($request->user()->id)->findOrFail($id);
        $experience->delete();

        return response()->json(['message' => 'Experiencia eliminada correctamente']);
    }

    private function databaseType(?string $type): string
    {
        return match ($type) {
            'academico' => 'academic',
            'freelance' => 'freelance',
            default => 'labor',
        };
    }
}
