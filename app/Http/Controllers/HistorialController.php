<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorialController extends Controller
{
    /**
     * GET /api/admin/historial/usuarios?q=&rol=&ciudad=&profesion=
     *
     * Búsqueda robusta compatible con PostgreSQL sin depender de UNACCENT.
     */
    public function buscarUsuarios(Request $request): JsonResponse
    {
        $q = preg_replace('/\s+/', ' ', trim((string) $request->query('q', '')));
        $rolFiltro = trim((string) $request->query('rol', ''));
        $ciudad = trim((string) $request->query('ciudad', ''));
        $profesion = trim((string) $request->query('profesion', ''));

        $hayBusqueda = $q !== '' || $rolFiltro !== '' || $ciudad !== '' || $profesion !== '';

        if (! $hayBusqueda) {
            return response()->json([]);
        }

        $query = Usuario::query()
            ->select('USER.*')
            ->join('USER_ROLE as ur', 'ur.id_user', '=', 'USER.id_user')
            ->join('ROLE as r', 'r.id_role', '=', 'ur.id_role')
            ->join('PROFILE as p', 'p.id_user_rol', '=', 'ur.id_user_role')
            ->leftJoin('JOB_TITLE as jt', 'jt.id_job_title', '=', 'p.id_job_title')
            ->leftJoin('STATE_COUNTRY as sc', 'sc.id_state_country', '=', 'p.id_state_country');

        if ($q !== '') {
            $words = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

            if (count($words) >= 2) {
                $nombre = $words[0];
                $apellido = implode(' ', array_slice($words, 1));

                $query->where(function (Builder $builder) use ($nombre, $apellido) {
                    $builder->whereRaw('"USER".name ILIKE ?', ["%{$nombre}%"])
                        ->whereRaw('"USER".last_name ILIKE ?', ["%{$apellido}%"]);
                });
            } else {
                $query->where(function (Builder $builder) use ($q) {
                    $builder->whereRaw('"USER".name ILIKE ?', ["%{$q}%"])
                        ->orWhereRaw('"USER".last_name ILIKE ?', ["%{$q}%"])
                        ->orWhereRaw('"USER".email ILIKE ?', ["%{$q}%"]);
                });
            }
        }

        if ($rolFiltro !== '') {
            $query->whereRaw('LOWER(r.name) = LOWER(?)', [$rolFiltro]);
        }

        if ($ciudad !== '') {
            $query->whereRaw('sc.name ILIKE ?', ["%{$ciudad}%"]);
        }

        if ($profesion !== '') {
            $query->whereRaw('jt.name ILIKE ?', ["%{$profesion}%"]);
        }

        $usuarios = $query->distinct()->limit(30)->get();

        $resultado = $usuarios->map(fn (Usuario $user) => [
            'id' => $user->id_user,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'email' => $user->email,
            'foto_perfil' => $user->foto_perfil ?: null,
            'rol' => $user->rol,
            'profesion' => $user->profesion,
            'ubicacion' => $user->ubicacion,
        ])->values();

        return response()->json($resultado);
    }

    /**
     * GET /api/admin/historial/usuarios/{id}
     * Devuelve todos los datos de un usuario para el panel de detalle.
     */
    public function datosUsuario(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::query()->find($id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json([
            'id' => $usuario->id_user,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $usuario->email,
            'foto_perfil' => $usuario->foto_perfil ?: null,
            'foto_portada' => $usuario->foto_portada ?: null,
            'rol' => $usuario->rol,
            'profesion' => $usuario->profesion,
            'ubicacion' => $usuario->ubicacion,
            'biografia' => $usuario->biografia,
            'perfil_completado' => $usuario->perfil_completado,
        ]);
    }

    /**
     * GET /api/admin/historial/usuarios/{id}/logs
     * Devuelve el historial LOG filtrado por tabla, tipo y rango de fechas.
     */
    public function logsUsuario(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::query()->find($id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $tabla = strtoupper(trim((string) $request->query('modified_table', $request->query('tabla', ''))));
        $tipo = strtolower(trim((string) $request->query('type', $request->query('tipo', ''))));
        $fechaInicio = trim((string) $request->query('fecha_inicio', $request->query('start_date', '')));
        $fechaFin = trim((string) $request->query('fecha_fin', $request->query('end_date', '')));
        $q = preg_replace('/\s+/', ' ', trim((string) $request->query('q', '')));

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(max((int) $request->query('per_page', 8), 1), 50);
        $offset = ($page - 1) * $perPage;

        $query = DB::table('LOG as l')
            ->join('USER_ROLE as ur', 'ur.id_user_role', '=', 'l.id_user_rol')
            ->where('ur.id_user', $id);

        if ($tabla !== '' && $tabla !== 'TODOS') {
            $query->whereRaw('UPPER(l.modified_table) = ?', [$tabla]);
        }

        if (in_array($tipo, ['create', 'update', 'delete'], true)) {
            $query->where('l.type', $tipo);
        }

        if ($fechaInicio !== '') {
            $query->whereDate('l.created_at', '>=', $fechaInicio);
        }

        if ($fechaFin !== '') {
            $query->whereDate('l.created_at', '<=', $fechaFin);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $like = '%' . mb_strtolower($q) . '%';

                $builder->whereRaw('LOWER(COALESCE(l.modified_table, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(l.modified_field, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(l.previous_value, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(l.new_value, \'\')) LIKE ?', [$like]);
            });
        }

        $baseQuery = clone $query;
        $total = $baseQuery->count();

        $logs = $query
            ->select([
                'l.id_log',
                'l.id_user_rol',
                'l.modified_table',
                'l.modified_field',
                'l.previous_value',
                'l.new_value',
                'l.created_at',
                'l.type',
            ])
            ->orderByDesc('l.created_at')
            ->orderByDesc('l.id_log')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'logs' => $logs,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) max(1, (int) ceil($total / $perPage)),
        ]);
    }
}
