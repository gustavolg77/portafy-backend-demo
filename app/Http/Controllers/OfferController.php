<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(protected OfferService $offerService) {}

    // Feed público — todas las ofertas abiertas
    public function index(Request $request): JsonResponse
    {
        $offers = Offer::with('skills')
            ->whereIn('state', ['open', 'visible'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($offers);
    }

    // Ofertas del reclutador autenticado
    public function mine(Request $request): JsonResponse
    {
        $profile = $request->user()->profileRecord();

        $offers = Offer::with('skills')
            ->where('id_profile', $profile->id_profile)
            ->whereNotIn('state', ['removed'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['offers' => $offers]);
    }

    // Detalle de una oferta
    public function show(int $id): JsonResponse
    {
        $offer = Offer::with('skills', 'profile')->findOrFail($id);

        return response()->json(['offer' => $offer]);
    }

    // Crear oferta
    public function store(Request $request): JsonResponse
    {
        try {
            $profile = $request->user()->profileRecord();

            $validated = $request->validate([
                'title'          => 'required|string|max:255',
                'description'    => 'nullable|string',
                'type'           => 'nullable|string',
                'modalidad'      => 'nullable|string',
                'ubicacion'      => 'nullable|string|max:255',
                'salary_min'     => 'nullable|integer',
                'salary_max'     => 'nullable|integer',
                'currency'       => 'nullable|string|max:10',
                'nivel'          => 'nullable|string',
                'area'           => 'nullable|string|max:255',
                'show_salary'    => 'nullable|boolean',
                'quota_quantity' => 'nullable|integer',
                'closed_at'      => 'nullable|date',
                'state'          => 'required|in:open,private',
                'skills'         => 'nullable|array',
                'skills.*'       => 'string|max:100',
                'banner'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $offer = $this->offerService->store(
                $validated,
                $profile,
                $request->file('banner') ?? null
            );

            return response()->json([
                'message' => 'Convocatoria creada exitosamente.',
                'offer'   => $offer,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Actualizar oferta
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $profile = $request->user()->profileRecord();
            $offer   = Offer::where('id_offer', $id)
                            ->where('id_profile', $profile->id_profile)
                            ->firstOrFail();

            $validated = $request->validate([
                'title'          => 'sometimes|string|max:255',
                'description'    => 'nullable|string',
                'type'           => 'nullable|string',
                'modalidad'      => 'nullable|string',
                'ubicacion'      => 'nullable|string|max:255',
                'salary_min'     => 'nullable|integer',
                'salary_max'     => 'nullable|integer',
                'currency'       => 'nullable|string|max:10',
                'nivel'          => 'nullable|string',
                'area'           => 'nullable|string|max:255',
                'show_salary'    => 'nullable|boolean',
                'quota_quantity' => 'nullable|integer',
                'closed_at'      => 'nullable|date',
                'state'          => 'nullable|in:open,visible,closed,private',
                'skills'         => 'nullable|array',
                'skills.*'       => 'string|max:100',
                'banner'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $offer = $this->offerService->update(
                $offer,
                $validated,
                $request->file('banner') ?? null
            );

            return response()->json([
                'message' => 'Convocatoria actualizada.',
                'offer'   => $offer,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Eliminar (soft delete)
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $profile = $request->user()->profileRecord();
            $offer   = Offer::where('id_offer', $id)
                            ->where('id_profile', $profile->id_profile)
                            ->firstOrFail();

            $this->offerService->destroy($offer);

            return response()->json(['message' => 'Convocatoria eliminada.']);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
