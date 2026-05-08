<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PostulationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_offer'  => 'required|integer|exists:OFFER,id_offer',
            'reason'    => 'nullable|string|max:255',
            'id_cv'     => 'nullable|integer|exists:CV,id_cv',
        ]);

        // El perfil del usuario autenticado
        $profile = DB::table('PROFILE')
            ->join('USER_ROLE', 'PROFILE.id_user_rol', '=', 'USER_ROLE.id_user_role')
            ->where('USER_ROLE.id_user', Auth::id())
            ->select('PROFILE.id_profile')
            ->first();

        if (!$profile) {
            return response()->json(['message' => 'Perfil no encontrado.'], 404);
        }

        // Verificar que la oferta esté abierta
        $offer = DB::table('OFFER')
            ->where('id_offer', $request->id_offer)
            ->whereIn('state', ['open', 'visible'])
            ->first();

        if (!$offer) {
            return response()->json(['message' => 'La oferta no está disponible.'], 422);
        }

        // Evitar postulación duplicada (ya lo cubre el UNIQUE INDEX, pero mejor fallar con mensaje claro)
        $exists = DB::table('POSTULATION')
            ->where('id_offer', $request->id_offer)
            ->where('id_postulant', $profile->id_profile)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya te postulaste a esta oferta.'], 409);
        }

        $id = DB::table('POSTULATION')->insertGetId([
    'id_offer'     => $request->id_offer,
    'id_postulant' => $profile->id_profile,
    'id_cv'        => $request->id_cv,
    'reason'       => $request->reason,
    'state'        => 'in_verification',
    'created_at'   => now(),
    'updated_at'   => now(),
], 'id_postulation');

        return response()->json([
            'message'        => 'Postulación registrada.',
            'id_postulation' => $id,
        ], 201);
    }

    // Para el reclutador: ver quiénes se postularon a su oferta
    public function index($id)
    {
        $postulations = DB::table('POSTULATION as p')
            ->join('PROFILE as pr', 'p.id_postulant', '=', 'pr.id_profile')
            ->join('USER_ROLE as ur', 'pr.id_user_rol', '=', 'ur.id_user_role')
            ->join('USER as u', 'ur.id_user', '=', 'u.id_user')
            ->leftJoin('JOB_TITLE as jt', 'pr.id_job_title', '=', 'jt.id_job_title')
            ->where('p.id_offer', $id)
            ->select(
                'p.id_postulation',
                'p.state',
                'p.reason',
                'p.created_at',
                'pr.id_profile',
                'pr.name',
                'pr.last_name',
                'pr.profile_photo',
                'pr.biography',
                'u.email',
                'jt.name as job_title',
            )
            ->orderBy('p.created_at', 'desc')
            ->get();

        return response()->json($postulations);
    }
}
