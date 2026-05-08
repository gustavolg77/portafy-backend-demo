<?php

namespace App\Services;

use App\Models\Experience;
use App\Models\FormacionAcademica;
use App\Models\Habilidad;
use App\Models\Proyecto;
use App\Models\Social;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProfileSearchService
{
    public function __construct(private readonly PublicAssetUrlService $assetUrlService) {}

    public function searchUsers(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        $usersQuery = $this->baseUserQuery();

        if (count($words) === 1) {
            $term = $words[0];

            $usersQuery->where(function (Builder $builder) use ($term) {
                $builder->where('USER.name', 'LIKE', "%{$term}%")
                    ->orWhere('USER.last_name', 'LIKE', "%{$term}%")
                    ->orWhereIn('USER.id_user', $this->skillUserIdsQuery($term));
            });
        } else {
            $nombre = array_shift($words);
            $apellido = implode(' ', $words);

            $usersQuery->where('USER.name', 'LIKE', "%{$nombre}%")
                ->where('USER.last_name', 'LIKE', "%{$apellido}%");
        }

        return $this->run($usersQuery);
    }

    public function search(string $query, string $category = 'usuario', string $filter = 'nombre'): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        return match ($category) {
            'usuario' => $this->searchUsuarios($query, $filter),
            'proyecto' => $this->searchUsuariosPorProyecto($query, $filter),
            'habilidad' => $this->searchUsuariosPorHabilidad($query, $filter),
            'experiencia' => $this->searchUsuariosPorExperiencia($query, $filter),
            'profesional' => $this->searchUsuariosPorFormacion($query, $filter),
            default => [],
        };
    }

    public function searchWithFilters(array $payload): array
    {
        $normalized = $this->normalizeFilterPayload($payload);
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($payload['per_page'] ?? 24)));

        $query = $this->baseUserQuery();

        $this->applyKeywordQuery($query, $normalized['q']);
        $this->applyUserFilters($query, $normalized['usuario']);
        $this->applySkillFilters($query, $normalized['habilidades']);
        $this->applyExperienceFilters($query, $normalized['experiencias']);
        $this->applyEducationFilters($query, $normalized['formaciones']);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->with(['profile.jobTitle', 'profile.stateCountry'])
            ->distinct()
            ->paginate($perPage, ['USER.*'], 'page', $page);

        $users = $paginator->getCollection();

        return [
            'data' => [
                'data' => $this->formatFilteredUsers($users),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => [
                'q' => $normalized['q'],
                'active_category' => $payload['active_category'] ?? null,
                'applied' => array_filter([
                    ...$normalized['usuario'],
                    ...$normalized['habilidades'],
                    ...$normalized['experiencias'],
                    ...$normalized['formaciones'],
                ], static fn ($value) => $value !== ''),
            ],
        ];
    }

    private function searchUsuarios(string $query, string $filter): array
    {
        $usersQuery = $this->baseUserQuery();

        match ($filter) {
            'bio' => $usersQuery->whereIn('USER.id_user', $this->profileUserIdsQuery('biography', $query)),
            'ubicacion' => $usersQuery->whereIn('USER.id_user', $this->stateCountryUserIdsQuery($query)),
            default => $this->applyNameFilter($usersQuery, $query),
        };

        return $this->run($usersQuery);
    }

    private function searchUsuariosPorProyecto(string $query, string $filter): array
    {
        $usersQuery = $this->baseUserQuery();

        $usersQuery->whereIn('USER.id_user', $this->projectUserIdsQuery($query, $filter));

        return $this->run($usersQuery);
    }

    private function searchUsuariosPorHabilidad(string $query, string $filter): array
    {
        $usersQuery = $this->baseUserQuery();

        $usersQuery->whereIn('USER.id_user', $this->skillUserIdsQuery($query, $filter));

        return $this->run($usersQuery);
    }

    private function searchUsuariosPorExperiencia(string $query, string $filter): array
    {
        $usersQuery = $this->baseUserQuery();

        $usersQuery->whereIn('USER.id_user', $this->experienceUserIdsQuery($query, $filter));

        return $this->run($usersQuery);
    }

    private function searchUsuariosPorFormacion(string $query, string $filter): array
    {
        $usersQuery = $this->baseUserQuery();

        $usersQuery->whereIn('USER.id_user', $this->educationUserIdsQuery($query, $filter));

        return $this->run($usersQuery);
    }

    private function baseUserQuery(): Builder
    {
        return Usuario::query()
            ->select('USER.*')
            ->join('USER_ROLE', 'USER_ROLE.id_user', '=', 'USER.id_user')
            ->join('PROFILE', 'PROFILE.id_user_rol', '=', 'USER_ROLE.id_user_role');
    }

    private function run(Builder $query): array
    {
        return $query
            ->distinct()
            ->limit(20)
            ->get()
            ->map(fn (Usuario $user) => $this->formatSearchUser($user))
            ->values()
            ->all();
    }

    private function formatSearchUser(Usuario $user): array
    {
        $skills = Habilidad::forUser($user->id)->orderByDesc('id_skill_profile')->get();

        return [
            'id' => $user->id,
            'name' => $user->nombre,
            'lastName' => $user->apellido,
            'photo' => $this->assetUrlService->fromStoragePath($user->foto_perfil),
            'bio' => $user->biografia,
            'skills' => $skills->pluck('nombre')->values(),
        ];
    }

    private function normalizeFilterPayload(array $payload): array
    {
        $flatFilters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];

        return [
            'q' => $this->cleanText($payload['q'] ?? ''),
            'usuario' => [
                'ubicacion' => $this->pickFilterValue($payload, $flatFilters, 'usuario', 'ubicacion'),
                'profesion' => $this->pickFilterValue($payload, $flatFilters, 'usuario', 'profesion'),
            ],
            'habilidades' => [
                'nombre' => $this->pickFilterValue($payload, $flatFilters, 'habilidades', 'nombre', 'habilidad'),
                'tipo' => $this->pickFilterValue($payload, $flatFilters, 'habilidades', 'tipo', 'hab_tipo'),
            ],
            'experiencias' => [
                'cargo' => $this->pickFilterValue($payload, $flatFilters, 'experiencias', 'cargo', 'exp_cargo'),
                'empresa' => $this->pickFilterValue($payload, $flatFilters, 'experiencias', 'empresa', 'exp_empresa'),
            ],
            'formaciones' => [
                'institucion' => $this->pickFilterValue($payload, $flatFilters, 'formaciones', 'institucion'),
                'nivel_formacion' => $this->pickFilterValue($payload, $flatFilters, 'formaciones', 'nivel_formacion'),
            ],
        ];
    }

    private function pickFilterValue(
        array $payload,
        array $flatFilters,
        string $group,
        string $nestedKey,
        ?string $flatKey = null
    ): string {
        $groupValues = is_array($payload[$group] ?? null) ? $payload[$group] : [];
        $value = $groupValues[$nestedKey] ?? $flatFilters[$flatKey ?? $nestedKey] ?? '';

        return $this->cleanText($value);
    }

    private function cleanText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function applyNameFilter(Builder $query, string $term): void
    {
        $words = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= 1) {
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('USER.name', 'LIKE', "%{$term}%")
                    ->orWhere('USER.last_name', 'LIKE', "%{$term}%");
            });

            return;
        }

        $nombre = array_shift($words);
        $apellido = implode(' ', $words);

        $query->where('USER.name', 'LIKE', "%{$nombre}%")
            ->where('USER.last_name', 'LIKE', "%{$apellido}%");
    }

    private function applyKeywordQuery(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($term) {
            $builder->where('USER.name', 'LIKE', "%{$term}%")
                ->orWhere('USER.last_name', 'LIKE', "%{$term}%")
                ->orWhere('USER.email', 'LIKE', "%{$term}%")
                ->orWhereIn('USER.id_user', $this->profileUserIdsQuery('biography', $term))
                ->orWhereIn('USER.id_user', $this->stateCountryUserIdsQuery($term))
                ->orWhereIn('USER.id_user', $this->skillUserIdsQuery($term))
                ->orWhereIn('USER.id_user', $this->experienceUserIdsQuery($term))
                ->orWhereIn('USER.id_user', $this->educationUserIdsQuery($term))
                ->orWhereIn('USER.id_user', $this->projectUserIdsQuery($term));
        });
    }

    private function applyUserFilters(Builder $query, array $filters): void
    {
        if (($filters['ubicacion'] ?? '') !== '') {
            $query->whereIn('USER.id_user', $this->stateCountryUserIdsQuery($filters['ubicacion']));
        }

        if (($filters['profesion'] ?? '') !== '') {
            $query->where(function (Builder $builder) use ($filters) {
                $builder->whereIn('USER.id_user', $this->jobTitleUserIdsQuery($filters['profesion']))
                    ->orWhereIn('USER.id_user', $this->experienceUserIdsQuery($filters['profesion'], 'cargo'))
                    ->orWhereIn('USER.id_user', $this->educationUserIdsQuery($filters['profesion'], 'carrera'));
            });
        }
    }

    private function applySkillFilters(Builder $query, array $filters): void
    {
        if (($filters['nombre'] ?? '') === '' && ($filters['tipo'] ?? '') === '') {
            return;
        }

        $query->whereIn('USER.id_user', $this->skillUserIdsQuery($filters['nombre'] ?? '', 'nombre', $filters['tipo'] ?? null));
    }

    private function applyExperienceFilters(Builder $query, array $filters): void
    {
        if (($filters['cargo'] ?? '') === '' && ($filters['empresa'] ?? '') === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($filters) {
            if (($filters['cargo'] ?? '') !== '') {
                $builder->whereIn('USER.id_user', $this->experienceUserIdsQuery($filters['cargo'], 'cargo'));
            }

            if (($filters['empresa'] ?? '') !== '') {
                $builder->whereIn('USER.id_user', $this->experienceUserIdsQuery($filters['empresa'], 'empresa'));
            }
        });
    }

    private function applyEducationFilters(Builder $query, array $filters): void
    {
        if (($filters['institucion'] ?? '') === '' && ($filters['nivel_formacion'] ?? '') === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($filters) {
            if (($filters['institucion'] ?? '') !== '') {
                $builder->whereIn('USER.id_user', $this->educationUserIdsQuery($filters['institucion'], 'universidad'));
            }

            if (($filters['nivel_formacion'] ?? '') !== '') {
                $builder->whereIn('USER.id_user', $this->educationUserIdsQuery($filters['nivel_formacion'], 'nivel'));
            }
        });
    }

    private function formatFilteredUser(Usuario $user): array
    {
        $skills = Habilidad::forUser($user->id)->orderByDesc('id_skill_profile')->get();
        $experiences = Experience::forUser($user->id)->orderByDesc('start_date')->orderByDesc('id_experience')->get();
        $education = FormacionAcademica::forUser($user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('id_university_career')
            ->get();
        $projects = Proyecto::forUser($user->id)->orderByDesc('id_project')->get();
        $socials = Social::forUser($user->id)->orderByDesc('id_social_networks')->get();

        return [
            'id' => $user->id,
            'type' => 'usuario',
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'email' => $user->email,
            'biografia' => $user->biografia,
            'ubicacion' => $user->ubicacion,
            'foto_perfil' => $this->assetUrlService->fromStoragePath($user->foto_perfil),
            'foto_portada' => $this->assetUrlService->fromStoragePath($user->foto_portada),
            'profesion' => $this->resolveProfessionLabel($user, $experiences, $education),
            'skills' => $skills->pluck('nombre')->values(),
            'habilidades' => $skills->map(fn (Habilidad $skill) => [
                'id' => $skill->id,
                'nombre' => $skill->nombre,
                'descripcion' => $skill->descripcion,
                'description' => $skill->description,
                'tipo' => $skill->tipo,
                'nivel_texto' => $skill->nivel_texto,
                'nivel_label' => $skill->nivel_label,
                'nivel_numero' => $skill->nivel_numero,
                'nivel_puntos' => $skill->nivel_puntos,
                'level_dots' => $skill->level_dots,
            ])->values(),
            'experiencias' => $experiences->map(fn (Experience $experience) => [
                'id' => $experience->id,
                'empresa' => $experience->company,
                'cargo' => $experience->title,
                'descripcion' => $experience->descripcion,
                'fecha_inicio' => optional($experience->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($experience->fecha_fin)->toDateString(),
                'actualmente' => $experience->isCurrent,
            ])->values(),
            'formaciones_academicas' => $education->map(fn (FormacionAcademica $item) => [
                'id' => $item->id,
                'institucion' => $item->institucion,
                'nivel_formacion' => $item->nivel_formacion,
                'nombre_programa' => $item->nombre_programa,
                'fecha_inicio' => optional($item->fecha_inicio)->toDateString(),
                'fecha_fin' => optional($item->fecha_fin)->toDateString(),
                'actualmente' => $item->isCurrent,
            ])->values(),
            'proyectos' => $projects->map(fn (Proyecto $project) => [
                'id' => $project->id,
                'titulo' => $project->titulo,
                'descripcion' => $project->descripcion,
                'tecnologias' => $project->tecnologias,
                'estado' => $project->estado,
            ])->values(),
            'socials' => [
                'cv_url' => $user->url_cv,
                'links' => $socials->values(),
            ],
            'profile_url' => '/perfil-profesional?usuario='.$user->id,
        ];
    }

    private function formatFilteredUsers(Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $users->loadMissing(['profile.jobTitle', 'profile.stateCountry']);

        $profileByUserId = $users
            ->mapWithKeys(fn (Usuario $user) => [
                $user->id => $user->profileRecord()?->getKey(),
            ])
            ->filter();

        $profileIds = $profileByUserId->values()->all();

        $skillsByProfile = \DB::table('SKILL_PROFILE')
            ->join('SKILL', 'SKILL.id_skill', '=', 'SKILL_PROFILE.id_skill')
            ->whereIn('id_profile', $profileIds)
            ->orderByDesc('SKILL_PROFILE.id_skill_profile')
            ->select('SKILL_PROFILE.id_profile', 'SKILL.name')
            ->get()
            ->groupBy('id_profile');

        return $users
            ->map(function (Usuario $user) use (
                $profileByUserId,
                $skillsByProfile
            ) {
                $profileId = $profileByUserId->get($user->id);

                return $this->formatFilteredUserFromCollections(
                    $user,
                    $skillsByProfile->get($profileId, collect())
                );
            })
            ->values()
            ->all();
    }

    private function formatFilteredUserFromCollections(
        Usuario $user,
        Collection $skills
    ): array {
        $skillNames = $skills
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->filter()
            ->values();

        return [
            'id' => $user->id,
            'type' => 'usuario',
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'email' => $user->email,
            'biografia' => $user->biografia,
            'ubicacion' => $user->ubicacion,
            'foto_perfil' => $this->assetUrlService->fromStoragePath($user->foto_perfil),
            'foto_portada' => $this->assetUrlService->fromStoragePath($user->foto_portada),
            'profesion' => trim((string) $user->profesion) ?: 'Profesional',
            'skills' => $skillNames,
            'habilidades' => $skillNames
                ->map(fn (string $skillName) => ['nombre' => $skillName])
                ->values(),
            'profile_url' => '/perfil-profesional?usuario='.$user->id,
        ];
    }

    private function resolveProfessionLabel(Usuario $user, Collection $experiences, Collection $education): string
    {
        $directProfession = trim((string) $user->profesion);
        if ($directProfession !== '') {
            return $directProfession;
        }

        $currentExperience = $experiences->firstWhere('actualmente', true) ?? $experiences->first();
        if ($currentExperience && ! empty($currentExperience->cargo)) {
            return (string) $currentExperience->cargo;
        }

        $firstEducation = $education->first();
        if ($firstEducation && ! empty($firstEducation->nombre_programa)) {
            return (string) $firstEducation->nombre_programa;
        }

        return 'Profesional';
    }

    private function profileUserIdsQuery(string $column, string $term)
    {
        return \DB::table('PROFILE')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->select('USER_ROLE.id_user')
            ->where("PROFILE.{$column}", 'LIKE', "%{$term}%");
    }

    private function stateCountryUserIdsQuery(string $term)
    {
        return \DB::table('PROFILE')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->join('STATE_COUNTRY', 'STATE_COUNTRY.id_state_country', '=', 'PROFILE.id_state_country')
            ->select('USER_ROLE.id_user')
            ->where('STATE_COUNTRY.name', 'LIKE', "%{$term}%");
    }

    private function jobTitleUserIdsQuery(string $term)
    {
        return \DB::table('PROFILE')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->join('JOB_TITLE', 'JOB_TITLE.id_job_title', '=', 'PROFILE.id_job_title')
            ->select('USER_ROLE.id_user')
            ->where('JOB_TITLE.name', 'LIKE', "%{$term}%");
    }

    private function skillUserIdsQuery(string $term, string $filter = 'nombre', ?string $type = null)
    {
        return \DB::table('SKILL_PROFILE')
            ->join('PROFILE', 'PROFILE.id_profile', '=', 'SKILL_PROFILE.id_profile')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->join('SKILL', 'SKILL.id_skill', '=', 'SKILL_PROFILE.id_skill')
            ->select('USER_ROLE.id_user')
            ->when($term !== '', function ($query) use ($term, $filter) {
                if ($filter === 'tipo') {
                    return $query->where('SKILL.type', $term === 'blanda' ? 'soft' : 'hard');
                }

                return $query->where('SKILL.name', 'LIKE', "%{$term}%");
            })
            ->when($type, fn ($query) => $query->where('SKILL.type', $type === 'blanda' ? 'soft' : 'hard'));
    }

    private function experienceUserIdsQuery(string $term, string $filter = 'cargo')
    {
        $column = match ($filter) {
            'empresa' => 'company',
            'descripcion' => 'description',
            default => 'title',
        };

        return \DB::table('EXPERIENCE')
            ->join('PROFILE', 'PROFILE.id_profile', '=', 'EXPERIENCE.id_profile')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->select('USER_ROLE.id_user')
            ->where("EXPERIENCE.{$column}", 'LIKE', "%{$term}%");
    }

    private function educationUserIdsQuery(string $term, string $filter = 'nivel')
    {
        return \DB::table('UNIVERSITY_CAREER')
            ->join('PROFILE', 'PROFILE.id_profile', '=', 'UNIVERSITY_CAREER.id_profile')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->leftJoin('UNIVERSITY', 'UNIVERSITY.id_university', '=', 'UNIVERSITY_CAREER.id_university')
            ->leftJoin('CAREER', 'CAREER.id_career', '=', 'UNIVERSITY_CAREER.id_career')
            ->select('USER_ROLE.id_user')
            ->where(match ($filter) {
                'universidad' => 'UNIVERSITY.name',
                'carrera' => 'CAREER.name',
                default => 'UNIVERSITY_CAREER.training_type',
            }, 'LIKE', "%{$term}%");
    }

    private function projectUserIdsQuery(string $term, string $filter = 'nombre')
    {
        $query = \DB::table('PROJECT')
            ->join('PROFILE', 'PROFILE.id_profile', '=', 'PROJECT.id_profile')
            ->join('USER_ROLE', 'USER_ROLE.id_user_role', '=', 'PROFILE.id_user_rol')
            ->select('USER_ROLE.id_user');

        return match ($filter) {
            'tecnologia' => $query
                ->join('PROJECT_SKILL', 'PROJECT_SKILL.id_project', '=', 'PROJECT.id_project')
                ->join('SKILL', 'SKILL.id_skill', '=', 'PROJECT_SKILL.id_skill')
                ->where('SKILL.name', 'LIKE', "%{$term}%"),
            'descripcion' => $query->where('PROJECT.description', 'LIKE', "%{$term}%"),
            default => $query->where('PROJECT.title', 'LIKE', "%{$term}%"),
        };
    }
}
