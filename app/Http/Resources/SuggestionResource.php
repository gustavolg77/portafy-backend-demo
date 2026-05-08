<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;
        $meta = $this->getMeta();

        return [
            'id' => (int) $this->id_suggestion,
            'id_profile' => (int) $this->id_profile,
            'description' => $this->description,
            'type' => $this->type,
            'meta' => $meta,
            'postulant' => [
                'name' => $profile ? "{$profile->name} {$profile->last_name}" : 'Usuario desconocido',
                'initials' => $this->getInitials($profile?->name, $profile?->last_name),
            ],
            'formattedDate' => $this->created_at?->format('d/m/Y H:i') ?? '',
            'state' => 'pending',
            'created_at' => $this->created_at?->toIsoString(),
            'updated_at' => $this->updated_at?->toIsoString(),
        ];
    }

    private function getInitials(?string $name, ?string $lastName): string
    {
        $first = $name ? strtoupper(substr($name, 0, 1)) : '';
        $last = $lastName ? strtoupper(substr($lastName, 0, 1)) : '';
        return $first . $last ?: '??';
    }
}