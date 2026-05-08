<?php

namespace App\Http\Controllers;

use App\Models\Preference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisibilityController extends Controller
{
    private const TYPE = 'personalization';

    private const SECTIONS = [
        'show_projects',
        'show_experience',
        'show_education',
        'show_skills',
        'show_links',
    ];

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profileRecord();

        if (! $profile) {
            return response()->json($this->defaults());
        }

        $preferences = Preference::query()
            ->where('id_profile', $profile->getKey())
            ->where('type', self::TYPE)
            ->whereIn('description', self::SECTIONS)
            ->get()
            ->keyBy('description');

        $result = [];
        foreach (self::SECTIONS as $section) {
            $result[$section] = (bool) ($preferences->get($section)?->visibility ?? true);
        }

        return response()->json($result);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'show_projects'   => 'boolean',
            'show_experience' => 'boolean',
            'show_education'  => 'boolean',
            'show_skills'     => 'boolean',
            'show_links'      => 'boolean',
        ]);

        $profile = $request->user()->profileRecord();

        if (! $profile) {
            return response()->json(['error' => 'Perfil no encontrado.'], 404);
        }

        $result = [];
        foreach (self::SECTIONS as $section) {
            if (! array_key_exists($section, $validated)) {
                continue;
            }

            $preference = Preference::updateOrCreate(
                [
                    'id_profile'  => $profile->getKey(),
                    'type'        => self::TYPE,
                    'description' => $section,
                ],
                [
                    'visibility' => (bool) $validated[$section],
                    'color'      => 'default',
                ]
            );

            $result[$section] = (bool) $preference->visibility;
        }

        return response()->json($result);
    }

    private function defaults(): array
    {
        return array_fill_keys(self::SECTIONS, true);
    }
}