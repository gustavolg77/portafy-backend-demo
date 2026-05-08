<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormacionAcademicaRequest;
use App\Models\FormacionAcademica;
use App\Support\OfficialSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormacionAcademicaController extends Controller
{
    public function store(FormacionAcademicaRequest $request): JsonResponse
    {
        try {
            $profile = OfficialSchema::ensureProfile($request->user());
            $university = OfficialSchema::ensureUniversity($request->input('institucion'));
            $career = OfficialSchema::ensureCareer($request->input('nombre_programa'));

            $formacion = FormacionAcademica::create([
                'training_type' => $request->input('nivel_formacion'),
                'start_date' => $request->input('fecha_inicio'),
                'end_date' => $request->input('fecha_fin'),
                'visibility' => true,
                'id_university' => $university->getKey(),
                'id_career' => $career->getKey(),
                'id_profile' => $profile->getKey(),
            ]);

            return response()->json([
                'message' => 'Formacion academica guardada correctamente',
                'formacion' => $formacion,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $formaciones = FormacionAcademica::forUser($request->user()->id)
                ->orderByDesc('start_date')
                ->orderByDesc('id_university_career')
                ->get();

            return response()->json([
                'formaciones' => $formaciones,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
