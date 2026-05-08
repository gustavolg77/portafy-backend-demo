<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function login(array $data): array
    {
        $usuario = Usuario::where('email', $data['email'])->first();

        if (! $usuario || ! Hash::check($data['password'], $usuario->getAuthPassword())) {
            throw new \Exception('Credenciales incorrectas');
        }

        $rol = DB::table('USER_ROLE as ur')
            ->join('ROLE as r', 'r.id_role', '=', 'ur.id_role')
            ->where('ur.id_user', $usuario->id_user)
            ->value('r.name');

        $usuario->rol = $rol;

        $token = $usuario->createToken('auth_token')->plainTextToken;
        $company = $usuario->profileRecord()?->company()->first();

        return [
            'user'  => $usuario,
            'token' => $token,
            'company' => $company,
        ];
    }

    public function register(array $data): array
{
    $usuario = new Usuario([
        'name' => $data['nombre'],
        'last_name' => $data['apellido'],
        'email' => $data['email'],
    ]);

    $roleMap = [
        'PROFESIONAL' => 'profesional',
        'RECLUTADOR' => 'reclutador',
    ];

    $usuario->save();

    $role = $roleMap[$data['role']] ?? 'profesional';

    $usuario->syncRole($role);

    $usuario->syncProfileData([
        'nombre' => $data['nombre'],
        'apellido' => $data['apellido'],
    ]);

    $usuario->syncPassword(Hash::make($data['password']));

    $token = $usuario->createToken('auth_token')->plainTextToken;

    return [
        'user'  => $usuario,
        'token' => $token,
    ];
}

}
