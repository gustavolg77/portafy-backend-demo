<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\CvDetail;
use App\Models\Usuario;
use Illuminate\Support\Collection;
use RuntimeException;
use Illuminate\Support\Facades\DB;

class CvService
{
    // ─── Obtener perfil del usuario ───────────────────────────────────────────

    private function getProfileId(Usuario $usuario): int
    {
        $profile = $usuario->profileRecord();

        if (! $profile) {
            throw new RuntimeException('El usuario no tiene un perfil asociado.');
        }

        return $profile->getKey();
    }

    // ─── Listar CVs ───────────────────────────────────────────────────────────

    public function list(Usuario $usuario): Collection
    {
        $profileId = $this->getProfileId($usuario);

        return Cv::forProfile($profileId)
            ->where('state', true)
            ->orderByDesc('updated_at')
            ->get();
    }

    // ─── Obtener un CV ────────────────────────────────────────────────────────

    public function find(int $cvId, Usuario $usuario): Cv
    {
        $profileId = $this->getProfileId($usuario);

        $cv = Cv::forProfile($profileId)->find($cvId);

        if (! $cv) {
            throw new RuntimeException('CV no encontrado.');
        }

        return $cv->load('details');
    }

    // ─── Crear CV ─────────────────────────────────────────────────────────────

    public function create(array $data, Usuario $usuario): Cv
    {
        $profileId = $this->getProfileId($usuario);

        $cv = Cv::create([
            'name_cv'     => $data['name_cv']     ?? 'Mi CV',
            'template'    => $data['template']    ?? 'navy',
            'font'        => $data['font']        ?? 'serif',
            'description' => $data['description'] ?? null,
            'state'       => true,
            'visible'     => $data['visible']     ?? false,
            'cv_url'      => $data['cv_url']      ?? null,
            'id_profile'  => $profileId,
        ]);

        $this->syncDetails($cv, $profileId);

        return $cv->load('details');
    }

    // ─── Actualizar CV ────────────────────────────────────────────────────────

    public function update(int $cvId, array $data, Usuario $usuario): Cv
    {
        $cv = $this->find($cvId, $usuario);

        $cv->update(array_filter([
            'name_cv'     => $data['name_cv']     ?? null,
            'template'    => $data['template']    ?? null,
            'font'        => $data['font']        ?? null,
            'description' => $data['description'] ?? null,
            'visible'     => $data['visible']     ?? null,
            'cv_url'      => $data['cv_url']      ?? null,
        ], fn($v) => ! is_null($v)));

        return $cv->fresh('details');
    }

    // ─── Eliminar CV (soft delete — state = false) ────────────────────────────

    public function delete(int $cvId, Usuario $usuario): void
    {
        $cv = $this->find($cvId, $usuario);
        $cv->update(['state' => false]);
    }

    // ─── Toggle visibilidad ───────────────────────────────────────────────────

    public function toggleVisible(int $cvId, Usuario $usuario): Cv
    {
        $cv = $this->find($cvId, $usuario);
        $cv->update(['visible' => ! $cv->visible]);

        return $cv->fresh();
    }

    private function syncDetails(Cv $cv, int $profileId): void
{
    CvDetail::where('id_cv', '=', $cv->id_cv, 'and')->delete();

    // Experiencias
    \Illuminate\Support\Facades\DB::table('EXPERIENCE')
        ->where('id_profile', $profileId)
        ->pluck('id_experience')
        ->each(fn($id) => CvDetail::create([
            'id_cv'         => $cv->id_cv,
            'id_experience' => $id,
            'visibility'    => true,
        ]));

    // Proyectos
    \Illuminate\Support\Facades\DB::table('PROJECT')
        ->where('id_profile', $profileId)
        ->pluck('id_project')
        ->each(fn($id) => CvDetail::create([
            'id_cv'      => $cv->id_cv,
            'id_project' => $id,
            'visibility' => true,
        ]));

    // Formaciones
    \Illuminate\Support\Facades\DB::table('UNIVERSITY_CAREER')
        ->where('id_profile', $profileId)
        ->pluck('id_university_career')
        ->each(fn($id) => CvDetail::create([
            'id_cv'                => $cv->id_cv,
            'id_university_career' => $id,
            'visibility'           => true,
        ]));

    // Habilidades
    \Illuminate\Support\Facades\DB::table('SKILL_PROFILE')
        ->where('id_profile', $profileId)
        ->pluck('id_skill_profile')
        ->each(fn($id) => CvDetail::create([
            'id_cv'            => $cv->id_cv,
            'id_skill_profile' => $id,
            'visibility'       => true,
        ]));
}

public function saveCustomEntry(int $cvId, array $data, Usuario $usuario): \App\Models\CvCustomEntry
{
    // Verificar que el CV pertenece al usuario
    $this->find($cvId, $usuario);

    return \App\Models\CvCustomEntry::create([
        'id_cv'       => $cvId,
        'entry_type'  => $data['entry_type'],
        'title'       => $data['title']       ?? null,
        'subtitle'    => $data['subtitle']    ?? null,
        'description' => $data['description'] ?? null,
        'date_start'  => $data['date_start']  ?? null,
        'date_end'    => $data['date_end']    ?? null,
        'is_current'  => $data['is_current']  ?? false,
        'visibility'  => true,
    ]);
}
}
