<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuggestionRequest;
use App\Models\Suggestion;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function __construct(private readonly SuggestionService $suggestionService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json($this->suggestionService->list([
                'search' => (string) $request->query('search', ''),
                'type' => (string) $request->query('type', 'todos'),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudieron obtener las sugerencias.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function context(Suggestion $suggestion): JsonResponse
    {
        try {
            return response()->json($this->suggestionService->context($suggestion));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo obtener el contexto de la sugerencia.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function accept(SuggestionRequest $request, Suggestion $suggestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            return response()->json(
                $this->suggestionService->accept(
                    $suggestion,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $validated['note'] ?? null
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo aceptar la sugerencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject(SuggestionRequest $request, Suggestion $suggestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            return response()->json(
                $this->suggestionService->reject(
                    $suggestion,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $validated['note'] ?? null
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo rechazar la sugerencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marca una sugerencia como en discusión.
     */
    public function discuss(SuggestionRequest $request, Suggestion $suggestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            return response()->json(
                $this->suggestionService->discuss(
                    $suggestion,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $validated['note'] ?? null
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo marcar la sugerencia como en discusión.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Escala una sugerencia a un nivel superior.
     */
    public function escalate(SuggestionRequest $request, Suggestion $suggestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            return response()->json(
                $this->suggestionService->escalate(
                    $suggestion,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $validated['note'] ?? null
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo escalar la sugerencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ignora una sugerencia.
     */
    public function ignore(SuggestionRequest $request, Suggestion $suggestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            return response()->json(
                $this->suggestionService->ignore(
                    $suggestion,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $validated['note'] ?? null
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo ignorar la sugerencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}