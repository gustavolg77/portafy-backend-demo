<?php

namespace App\Http\Controllers;

use App\Services\CvImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CvImportController extends Controller
{
    public function __construct(private readonly CvImportService $cvImportService) {}

    /**
     * POST /api/cv/importar
     *
     * Recibe un archivo (PDF, DOCX, DOC, TXT, JPG, PNG, WEBP) y devuelve
     * los datos del CV estructurados en JSON para pre-rellenar los formularios.
     *
     * Form-data:
     *   cv_file  →  archivo (requerido, máx 10 MB)
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'cv_file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:pdf,doc,docx,txt,jpg,jpeg,png,webp',
            ],
        ], [
            'cv_file.required' => 'Debes adjuntar un archivo.',
            'cv_file.file'     => 'El archivo no es válido.',
            'cv_file.max'      => 'El archivo no debe superar los 10 MB.',
            'cv_file.mimes'    => 'Formato no soportado. Sube PDF, Word, imagen (JPG/PNG/WEBP) o TXT.',
        ]);

        try {
            $data = $this->cvImportService->import($request->file('cv_file'));

            return response()->json([
                'status'  => 'success',
                'message' => 'CV procesado correctamente.',
                'data'    => $data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error inesperado al procesar el CV. Intenta de nuevo.',
            ], 500);
        }
    }
}
