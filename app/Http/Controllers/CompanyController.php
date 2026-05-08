<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Country;
use App\Http\Requests\StoreCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\CloudinaryUploader;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            $profile = $request->user()->profile;

            if ($profile->id_company) {
                return response()->json([
                    'message' => 'Este perfil ya tiene una empresa registrada.',
                ], 422);
            }

            $logoUrl = null;
            if ($request->hasFile('logo')) {
                $logoUrl = CloudinaryUploader::upload($request->file('logo'), 'logos');
            }

            $country = Country::where('name', $request->country_name)->firstOrFail();

            $company = Company::create([
                'name'         => $request->name,
                'description'  => $request->description,
                'logo_url'     => $logoUrl,
                'industry'     => $request->industry,
                'city'         => $request->city,
                'id_country'   => $country->id_country,
                'phone_prefix' => $request->phone_prefix,
                'phone'        => $request->phone,
                'website'      => $request->website,
            ]);

            $profile->update(['id_company' => $company->id_company]);
            $profile->update(['completed_profile' => 1]);

            return response()->json([
                'message' => 'Empresa registrada exitosamente.',
                'company' => $company,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    public function update(Request $request): JsonResponse
    {
        try {
            $user    = $request->user();
            $profile = $request->user()->profileRecord();
            Log::info('debug update', [
                'user_id'    => $user?->id_user,
                'profile_id' => $profile?->id_profile,
                'id_company' => $profile?->id_company,
            ]);

            if (!$profile) {
                return response()->json(['message' => 'Perfil no encontrado.'], 404);
            }

            $company = Company::where('id_company', $profile->id_company)->first();

            if (!$company) {
                return response()->json(['message' => 'No tienes empresa registrada.'], 404);
            }
            Log::info('company encontrada', ['id' => $company->id_company]);

            $validated = $request->validate([
                'name'        => 'sometimes|string|min:2|max:255',
                'description' => 'sometimes|string|max:500',
                'mission'     => 'sometimes|nullable|string|max:500',
                'vision'      => 'sometimes|nullable|string|max:500',
                'industry'    => 'sometimes|string|max:255',
                'city'        => 'sometimes|string|max:255',
                'phone'       => 'sometimes|string|max:30',
                'website'     => 'nullable|url|max:255',
                'logo'        => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
                'banner'      => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            ]);

            Log::info('validated', $validated);

            if ($request->hasFile('logo')) {
                Log::info('subiendo logo');
                $validated['logo_url'] = CloudinaryUploader::upload(
                    $request->file('logo'), 'logos'
                );
            }

            if ($request->hasFile('banner')) {
                Log::info('subiendo banner');
                $validated['banner_url'] = CloudinaryUploader::upload(
                    $request->file('banner'), 'banners'
                );
            }

            unset($validated['logo'], $validated['banner']);
            Log::info('antes de update', $validated);
            $company->update($validated);
            Log::info('update ok');

            return response()->json([
                'message' => 'Empresa actualizada.',
                'company' => $company->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('error update company', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);
        return response()->json(['message' => $e->getMessage()], 422);
        }
    }
    public function show($id)
    {
        $company = \App\Models\Company::where('id_company', $id)->first();

        if (!$company) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        return response()->json([
            'company' => $company
        ]);
    }

}

