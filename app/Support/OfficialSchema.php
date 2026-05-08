<?php

namespace App\Support;

use App\Models\Career;
use App\Models\Country;
use App\Models\JobTitle;
use App\Models\Platform;
use App\Models\Profile;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Skill;
use App\Models\StateCountry;
use App\Models\University;
use App\Models\UserRole;
use App\Models\Usuario;

class OfficialSchema
{
    public static function normalizeRole(?string $role): string
    {
        $normalized = is_string($role) ? trim(mb_strtolower($role)) : '';

        return $normalized !== '' ? $normalized : 'usuario';
    }

    public static function ensureRole(string $roleName): Role
    {
        return Role::firstOrCreate(
            ['name' => self::normalizeRole($roleName)],
            ['description' => 'Rol sincronizado desde la API', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensureUserRole(Usuario $user, ?string $roleName = null): UserRole
    {
        $role = self::ensureRole($roleName ?? $user->rol ?? 'usuario');

        return UserRole::firstOrCreate([
            'id_user' => $user->getKey(),
            'id_role' => $role->getKey(),
        ]);
    }

    public static function ensureProfile(Usuario $user): Profile
    {
        $userRole = self::ensureUserRole($user, $user->rol);

        return Profile::firstOrCreate(
            ['id_user_rol' => $userRole->getKey()],
            [
                'name' => $user->getRawOriginal('name') ?: 'Usuario',
                'last_name' => $user->getRawOriginal('last_name') ?: '',
                'completed_profile' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function ensureCountry(string $name = 'No especificado'): Country
    {
        $normalized = trim($name) !== '' ? trim($name) : 'No especificado';

        return Country::firstOrCreate(
            ['name' => $normalized],
            ['state' => 'activate', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensureStateCountry(?string $location): ?StateCountry
    {
        $location = is_string($location) ? trim($location) : '';

        if ($location === '') {
            return null;
        }

        $country = self::ensureCountry();

        return StateCountry::firstOrCreate(
            [
                'id_country' => $country->getKey(),
                'name' => $location,
            ],
            [
                'state' => 'activate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function ensureJobTitle(?string $profession): ?JobTitle
    {
        $profession = is_string($profession) ? trim($profession) : '';

        if ($profession === '') {
            return null;
        }

        return JobTitle::firstOrCreate(
            ['name' => $profession, 'id_area' => null],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensurePlatform(string $name): Platform
    {
        $normalized = trim(mb_strtolower($name));

        return Platform::firstOrCreate(
            ['name' => $normalized],
            [
                'url_platform' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function ensureUniversity(string $name): University
    {
        return University::firstOrCreate(
            ['name' => trim($name)],
            ['state' => 'activate', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensureCareer(string $name): Career
    {
        return Career::firstOrCreate(
            ['name' => trim($name)],
            ['state' => 'activate', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public static function ensureSkill(string $name, string $type, ?string $description = null): Skill
    {
        $skill = Skill::firstOrCreate(
            [
                'name' => trim($name),
                'type' => self::databaseSkillType($type),
            ],
            [
                'state' => 'activate',
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (filled($description) && $skill->description !== $description) {
            $skill->forceFill(['description' => $description])->save();
        }

        return $skill;
    }

    public static function databaseSkillType(?string $type): string
    {
        return mb_strtolower((string) $type) === 'blanda' ? 'soft' : 'hard';
    }

    public static function apiSkillType(?string $type): string
    {
        return mb_strtolower((string) $type) === 'soft' ? 'blanda' : 'tecnica';
    }

    public static function databaseSkillLevel(?string $textLevel, ?int $numericLevel): string
    {
        $normalized = is_string($textLevel) ? mb_strtolower(trim($textLevel)) : null;

        return match (true) {
            $normalized === 'experto',
            $normalized === 'avanzado',
            ($numericLevel ?? 0) >= 80 => 'senior',
            $normalized === 'intermedio',
            ($numericLevel ?? 0) >= 50 => 'mid',
            default => 'junior',
        };
    }

    public static function apiSkillLevelText(?string $level): string
    {
        return match (mb_strtolower((string) $level)) {
            'senior' => 'avanzado',
            'mid' => 'intermedio',
            default => 'basico',
        };
    }

    public static function apiSkillLevelNumber(?string $level): int
    {
        return match (mb_strtolower((string) $level)) {
            'senior' => 85,
            'mid' => 60,
            default => 30,
        };
    }

    public static function databaseProjectState(?string $state): string
    {
        return match (mb_strtolower((string) $state)) {
            'completado' => 'completed',
            'pausado' => 'removed',
            default => 'in_progress',
        };
    }

    public static function apiProjectState(?string $state): string
    {
        return match (mb_strtolower((string) $state)) {
            'completed' => 'completado',
            'removed' => 'pausado',
            default => 'en_progreso',
        };
    }

    public static function splitTechnologies(?string $technologies): array
    {
        if (! is_string($technologies) || trim($technologies) === '') {
            return [];
        }

        $parts = preg_split('/,/', $technologies) ?: [];

        return array_values(array_unique(array_filter(array_map(
            static fn (string $item) => trim($item),
            $parts
        ))));
    }

    public static function synchronizeProvider(Profile $profile, string $providerName, string $providerUserId): Provider
    {
        $provider = Provider::firstOrNew([
            'id_profile' => $profile->getKey(),
            'provider' => trim(mb_strtolower($providerName)),
        ]);

        $provider->provider_user_id = $providerUserId;
        $provider->active = true;
        $provider->save();

        return $provider;
    }
}
