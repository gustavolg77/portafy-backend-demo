<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Skill;
use App\Models\OfferDetail;
use App\Support\CloudinaryUploader;
use Illuminate\Http\Request;

class OfferService
{
    public function store(array $data, $profile, ?object $bannerFile = null): Offer
    {
        $bannerUrl = null;
        if ($bannerFile) {
            $bannerUrl = CloudinaryUploader::upload($bannerFile, 'offers');
        }

        $offer = Offer::create([
            'title'          => $data['title'],
            'description'    => $data['description']    ?? null,
            'type'           => $data['type']           ?? null,
            'modalidad'      => $data['modalidad']      ?? null,
            'ubicacion'      => $data['ubicacion']      ?? null,
            'salary_min'     => $data['salary_min']     ?? null,
            'salary_max'     => $data['salary_max']     ?? null,
            'currency'       => $data['currency']       ?? 'USD',
            'nivel'          => $data['nivel']          ?? null,
            'area'           => $data['area']           ?? null,
            'show_salary'    => $data['show_salary']    ?? true,
            'quota_quantity' => $data['quota_quantity'] ?? null,
            'closed_at'      => $data['closed_at']      ?? null,
            'banner_url'     => $bannerUrl,
            'state'          => $data['state'],         // 'open' o 'private' (borrador)
            'id_profile'     => $profile->id_profile,
        ]);

        // Guardar skills si vienen
        if (!empty($data['skills'])) {
            $this->syncSkills($offer, $data['skills']);
        }

        return $offer->load('skills');
    }

    public function update(Offer $offer, array $data, ?object $bannerFile = null): Offer
    {
        if ($bannerFile) {
            $data['banner_url'] = CloudinaryUploader::upload($bannerFile, 'offers');
        }

        $offer->update(array_filter([
            'title'          => $data['title']          ?? $offer->title,
            'description'    => $data['description']    ?? $offer->description,
            'type'           => $data['type']           ?? $offer->type,
            'modalidad'      => $data['modalidad']      ?? $offer->modalidad,
            'ubicacion'      => $data['ubicacion']      ?? $offer->ubicacion,
            'salary_min'     => $data['salary_min']     ?? $offer->salary_min,
            'salary_max'     => $data['salary_max']     ?? $offer->salary_max,
            'currency'       => $data['currency']       ?? $offer->currency,
            'nivel'          => $data['nivel']          ?? $offer->nivel,
            'area'           => $data['area']           ?? $offer->area,
            'show_salary'    => $data['show_salary']    ?? $offer->show_salary,
            'quota_quantity' => $data['quota_quantity'] ?? $offer->quota_quantity,
            'closed_at'      => $data['closed_at']      ?? $offer->closed_at,
            'banner_url'     => $data['banner_url']     ?? $offer->banner_url,
            'state'          => $data['state']          ?? $offer->state,
        ], fn($v) => $v !== null));

        if (isset($data['skills'])) {
            $this->syncSkills($offer, $data['skills']);
        }

        return $offer->fresh('skills');
    }

    public function destroy(Offer $offer): void
    {
        $offer->update(['state' => 'removed']);
    }

    private function syncSkills(Offer $offer, array $skillNames): void
    {
        // Eliminar skills anteriores
        OfferDetail::where('id_offer', $offer->id_offer)->delete();

        foreach ($skillNames as $name) {
            // Buscar o crear la skill
            $skill = Skill::firstOrCreate(
                ['name' => trim($name)],
                ['state' => 'activate', 'type' => 'hard']
            );

            OfferDetail::create([
                'id_offer' => $offer->id_offer,
                'id_skill' => $skill->id_skill,
            ]);
        }
    }
}
