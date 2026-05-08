<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthUserService
{
    public function resolveOrCreateUser(
        string $provider,
        string $providerId,
        ?string $email,
        string $nombre,
        string $apellido = '',
        ?string $avatar = null,
        string $role = 'PROFESIONAL'
    ): Usuario {

        $normalizedEmail = $email ? trim(mb_strtolower($email)) : null;

        $user = $normalizedEmail
            ? Usuario::where('email', $normalizedEmail)->first()
            : null;

        $user ??= Usuario::findByProvider($provider, $providerId);

        if ($user) {

            $user->syncCoreData(array_filter([
                'nombre' => blank($user->nombre) ? $nombre : null,
                'apellido' => blank($user->apellido) ? $apellido : null,
                'email' => $normalizedEmail && blank($user->email) ? $normalizedEmail : null,
            ], fn($v) => $v !== null));

            $user->syncProfileData(array_filter([
                'foto_perfil' => blank($user->foto_perfil) ? $avatar : null,
            ], fn($v) => $v !== null));

            $user->syncProvider($provider, $providerId);

            return $user;
        }

        if (!$normalizedEmail) {
            throw new RuntimeException('No se pudo obtener el email del proveedor.');
        }

        $user = new Usuario([
            'email' => $normalizedEmail,
            'name' => $nombre ?: 'Usuario',
            'last_name' => $apellido,
        ]);

        $user->save();

        $roleMap = [
            'PROFESIONAL' => 'profesional',
            'RECLUTADOR' => 'reclutador',
        ];

        $finalRole = $roleMap[$role] ?? 'profesional';

        $user->syncRole($finalRole);

        $user->syncProfileData([
            'nombre' => $nombre ?: 'Usuario',
            'apellido' => $apellido,
            'foto_perfil' => $avatar,
        ]);

        $user->syncProvider($provider, $providerId);

        $user->syncPassword(Hash::make(Str::random(24)));

        return $user;
    }

    public function issueToken(Usuario $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}
