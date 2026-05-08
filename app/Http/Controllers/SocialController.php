<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialRequest;
use App\Models\Social;
use App\Support\OfficialSchema;
use App\Support\ProfileCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $socials = Social::forUser($user->id)
            ->orderByDesc('id_social_networks')
            ->get();

        return response()->json([
            'links' => $socials->values(),
            'platforms' => ProfileCatalog::socialPlatforms(),
        ]);
    }

    public function store(SocialRequest $request): JsonResponse
    {
        $data = $request->persistenceData();
        $user = $request->user();

        $profile = OfficialSchema::ensureProfile($user);
        $platform = OfficialSchema::ensurePlatform($data['plataforma']);

        $social = Social::updateOrCreate(
            [
                'id_profile' => $profile->getKey(),
                'id_platform' => $platform->getKey(),
            ],
            [
                'url' => $data['url'],
                'public' => true,
            ]
        );

        return response()->json($social->fresh(['platform', 'profile.userRole']), 201);
    }

    public function update(SocialRequest $request, int $id): JsonResponse
    {
        $social = Social::forUser($request->user()->id)->findOrFail($id);
        $data = $request->persistenceData();

        $payload = [
            'url' => $data['url'] ?? $social->url,
        ];

        if (isset($data['plataforma'])) {
            $payload['id_platform'] = OfficialSchema::ensurePlatform($data['plataforma'])->getKey();
        }

        $social->update($payload);

        return response()->json($social->fresh(['platform', 'profile.userRole']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $social = Social::forUser($request->user()->id)->findOrFail($id);
        $social->delete();

        return response()->json(['message' => 'Enlace social eliminado correctamente']);
    }
}
