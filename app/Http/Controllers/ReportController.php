<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Publication;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json($this->reportService->list([
                'search' => (string) $request->query('search', ''),
                'motivo' => (string) $request->query('motivo', 'todos'),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudieron obtener los reportes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function motivos(): JsonResponse
    {
        return response()->json([
            'data' => $this->reportService->reportMotivos(),
        ]);
    }

    public function storePublication(Request $request, Publication $publication): JsonResponse
    {
        try {
            $validated = $request->validate([
                'motivo' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
                'tests_url' => ['nullable', 'string', 'max:255'],
            ]);

            return response()->json(
                $this->reportService->reportPublication($request->user(), $publication, $validated),
                201
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo enviar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, Report $report): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
            ]);

            return response()->json(
                $this->reportService->rejectAndAttend(
                    $report,
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $request->user()
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo registrar la eliminacion del reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function attend(Request $request, Report $report): JsonResponse
    {
        try {
            $validated = $request->validate([
                'administrator_profile_id' => ['nullable', 'integer'],
                'state' => ['required', 'string', 'in:accepted,ignored,higher,rejected'],
                'action_taken' => ['nullable', 'string', 'max:255'],
                'test_url' => ['nullable', 'string', 'max:255'],
            ]);

            return response()->json(
                $this->reportService->attend(
                    $report,
                    [
                        'state' => (string) $validated['state'],
                        'action_taken' => (string) ($validated['action_taken'] ?? ''),
                        'test_url' => (string) ($validated['test_url'] ?? ''),
                    ],
                    isset($validated['administrator_profile_id']) ? (int) $validated['administrator_profile_id'] : null,
                    $request->user()
                )
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo registrar la atencion del reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function context(Report $report): JsonResponse
    {
        try {
            return response()->json($this->reportService->context($report));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo obtener el contexto del reporte.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
