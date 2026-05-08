<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\SearchFiltersRequest;
use App\Models\Usuario;
use App\Services\ProfileSearchService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly ProfileSearchService $profileSearchService
    ) {}

    public function storeOrUpdate(ProfileRequest $request): JsonResponse
    {
        try {
            $usuario = $this->profileService->storeOrUpdate($request);

            return response()->json([
                'status' => 'success',
                'message' => 'Perfil actualizado correctamente',
                'data' => $usuario,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->profileService->show($request->user()));
    }

    public function overview(Request $request): JsonResponse
    {
        return response()->json($this->profileService->overview($request->user()));
    }

    public function publicOverview(Usuario $usuario): JsonResponse
    {
        return response()->json($this->profileService->overview($usuario));
    }

    public function completar(ProfileRequest $request): JsonResponse
    {
        try {
            $this->profileService->completar($request);

            return response()->json([
                'message' => 'Perfil completado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function crearPerfilProfesional(ProfileRequest $request): JsonResponse
    {
        try {
            $usuario = $this->profileService->crearPerfilProfesional($request);

            return response()->json([
                'message' => 'Perfil profesional actualizado correctamente',
                'usuario' => $usuario,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchUsers(Request $request): JsonResponse
    {
        try {
            return response()->json(
                $this->profileSearchService->searchUsers((string) $request->query('q', ''))
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en la busqueda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            return response()->json($this->profileSearchService->search(
                (string) $request->query('q', ''),
                (string) $request->query('category', 'usuario'),
                (string) $request->query('filter', 'nombre'),
            ));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en busqueda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchWithFilters(SearchFiltersRequest $request): JsonResponse
    {
        try {
            return response()->json(
                $this->profileSearchService->searchWithFilters($request->validated())
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error en busqueda por filtros',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
