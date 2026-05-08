<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AgregarAdminService
{
    /**
     * @param  array $data  Datos validados:
     *                      { nombre, apellido, email, numero?, password }
     * @return array        Datos del admin creado para la respuesta JSON
     * @throws \Exception   Si el email ya existe o falla algún paso
     */
    public function crearAdministrador(array $data): array
{
    $usuario = Usuario::create([
        'name'      => $data['nombre'],
        'last_name' => $data['apellido'],
        'email'     => $data['email'],
        'number'    => $data['numero'] ?? null,
    ]);

    $usuario->syncRole('administrador');

    $usuario->syncProfileData([
        'nombre'   => $data['nombre'],
        'apellido' => $data['apellido'],
    ]);

    $usuario->syncPassword(Hash::make($data['password']));

    $usuario->refresh();
    $usuario->unsetRelation('profile');

    Log::info('Administrador creado', [
        'id_user' => $usuario->id_user,
        'email'   => $usuario->email,
    ]);

    return [
        'id_user'  => $usuario->id_user,
        'nombre'   => $usuario->nombre,
        'apellido' => $usuario->apellido,
        'email'    => $usuario->email,
        'rol'      => $usuario->rol,
    ];
}
}
