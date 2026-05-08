<?php

namespace App\Http\Controllers;

use App\Http\Requests\SkillRequest;
use App\Models\Habilidad;
use App\Support\OfficialSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $skills = Habilidad::forUser($request->user()->id)
            ->orderByDesc('id_skill_profile')
            ->get();

        return response()->json($skills);
    }

    public function store(SkillRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = OfficialSchema::ensureProfile($user);
        $data = $request->persistenceData();
        $skill = OfficialSchema::ensureSkill($data['nombre'], $data['tipo'], $data['descripcion'] ?? null);

        $skillProfile = Habilidad::updateOrCreate(
            [
                'id_profile' => $profile->getKey(),
                'id_skill' => $skill->getKey(),
            ],
            [
                'level' => OfficialSchema::databaseSkillLevel(
                    $data['nivel_texto'] ?? null,
                    $data['nivel_numero'] ?? null
                ),
                'visibility' => true,
            ]
        );

        return response()->json($skillProfile->fresh(['skill', 'profile.userRole']), 201);
    }

    public function update(SkillRequest $request, int $id): JsonResponse
    {
        $skillProfile = Habilidad::forUser($request->user()->id)->findOrFail($id);
        $data = $request->persistenceData();
        $skill = OfficialSchema::ensureSkill($data['nombre'], $data['tipo'], $data['descripcion'] ?? null);

        $skillProfile->update([
            'id_skill' => $skill->getKey(),
            'level' => OfficialSchema::databaseSkillLevel(
                $data['nivel_texto'] ?? null,
                $data['nivel_numero'] ?? null
            ),
        ]);

        return response()->json($skillProfile->fresh(['skill', 'profile.userRole']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $skillProfile = Habilidad::forUser($request->user()->id)->findOrFail($id);
        $skillProfile->delete();

        return response()->json(['message' => 'Habilidad eliminada correctamente']);
    }
}
