<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Services\AgregarAdminService;
use Illuminate\Http\JsonResponse;

/**
 * AgregarAdminController
 * Endpoint para crear un nuevo administrador desde el panel admin.
 *
 * Ubicación: app/Http/Controllers/Admin/AgregarAdminController.php
 */
class AgregarAdminController extends Controller
{
    public function __construct(
        private readonly AgregarAdminService $adminService
    ) {}

    /**
     * POST /api/admin/users
     *
     * Body esperado (viene del frontend adminService.js):
     * {
     *   "nombre":                "María",
     *   "apellido":              "García",
     *   "email":                 "maria@portafy.com",
     *   "numero":                70012345,           // opcional
     *   "password":              "secreto123",
     *   "password_confirmation": "secreto123"
     * }
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        try {
            $admin = $this->adminService->crearAdministrador(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Administrador creado exitosamente.',
                'data'    => $admin,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            // Email o número duplicado a nivel de DB
            return response()->json([
                'success' => false,
                'message' => 'El correo o número ya está registrado.',
            ], 409);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el administrador.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
