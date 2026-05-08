<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DefinitionCatalogController extends Controller
{
    public function store(Request $request, string $catalog): JsonResponse
    {
        $validated = match ($catalog) {
            'areas' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('AREA', 'name')],
                'description' => ['required', 'string', 'max:255'],
            ]),
            'countries' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('COUNTRY', 'name')],
                'state' => ['required', Rule::in(['activate', 'deactivate'])],
            ]),
            'skills' => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'state' => ['required', Rule::in(['activate', 'deactivate'])],
                'type' => ['required', Rule::in(['soft', 'hard'])],
                'quantitative_level' => ['nullable', 'string', 'max:255'],
                'qualitative_level' => ['nullable', 'integer', 'min:0'],
                'description' => ['required', 'string', 'max:255'],
                'id_area' => ['required', 'integer', Rule::exists('AREA', 'id_area')],
            ]),
            'states' => $request->validate([
                'id_country' => ['required', 'integer', Rule::exists('COUNTRY', 'id_country')],
                'name' => ['required', 'string', 'max:255'],
                'state' => ['required', Rule::in(['activate', 'deactivate'])],
            ]),
            'universities' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('UNIVERSITY', 'name')],
                'description' => ['nullable', 'string', 'max:255'],
                'state' => ['required', Rule::in(['activate', 'deactivate'])],
            ]),
            'careers' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('CAREER', 'name')],
                'description' => ['nullable', 'string', 'max:255'],
                'state' => ['required', Rule::in(['activate', 'deactivate'])],
            ]),
            'companies' => $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:500'],
                'logo_url' => ['nullable', 'string', 'max:255'],
                'industry' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'id_country' => ['required', 'integer', Rule::exists('COUNTRY', 'id_country')],
                'phone_prefix' => ['nullable', 'string', 'max:10'],
                'phone' => ['required', 'string', 'max:30'],
                'website' => ['nullable', 'url', 'max:255'],
                'state' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
                'banner_url' => ['nullable', 'string', 'max:255'],
                'mission' => ['nullable', 'string', 'max:500'],
                'vision' => ['nullable', 'string', 'max:500'],
            ]),
            default => null,
        };

        abort_if($validated === null, 404, 'Catalogo no encontrado.');

        $table = match ($catalog) {
            'areas' => 'AREA',
            'countries' => 'COUNTRY',
            'skills' => 'SKILL',
            'states' => 'STATE_COUNTRY',
            'universities' => 'UNIVERSITY',
            'careers' => 'CAREER',
            'companies' => 'COMPANY',
        };

        $primaryKey = match ($catalog) {
            'areas' => 'id_area',
            'countries' => 'id_country',
            'skills' => 'id_skill',
            'states' => 'id_state_country',
            'universities' => 'id_university',
            'careers' => 'id_career',
            'companies' => 'id_company',
        };

        $payload = collect($validated)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        $now = now();
        $id = DB::table($table)->insertGetId([
            ...$payload,
            'created_at' => $now,
            'updated_at' => $now,
        ], $primaryKey);

        return response()->json([
            'message' => 'Registro creado correctamente.',
            'id' => $id,
        ], 201);
    }

    public function index(string $catalog): JsonResponse
    {
        $records = match ($catalog) {
            'areas' => DB::table('AREA')
                ->select('id_area', 'name', 'description', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get(),
            'countries' => DB::table('COUNTRY')
                ->select('id_country', 'name', 'state', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get(),
            'skills' => DB::table('SKILL')
                ->leftJoin('AREA', 'AREA.id_area', '=', 'SKILL.id_area')
                ->select(
                    'SKILL.id_skill',
                    'SKILL.name',
                    'SKILL.state',
                    'SKILL.type',
                    'SKILL.quantitative_level',
                    'SKILL.qualitative_level',
                    'SKILL.description',
                    'SKILL.created_at',
                    'SKILL.updated_at',
                    'SKILL.id_area',
                    'AREA.name as area_name'
                )
                ->orderBy('SKILL.name')
                ->get(),
            'states' => DB::table('STATE_COUNTRY')
                ->leftJoin('COUNTRY', 'COUNTRY.id_country', '=', 'STATE_COUNTRY.id_country')
                ->select(
                    'STATE_COUNTRY.id_state_country',
                    'STATE_COUNTRY.id_country',
                    'STATE_COUNTRY.name',
                    'STATE_COUNTRY.state',
                    'STATE_COUNTRY.created_at',
                    'STATE_COUNTRY.updated_at',
                    'COUNTRY.name as country_name'
                )
                ->orderBy('STATE_COUNTRY.name')
                ->get(),
            'universities' => DB::table('UNIVERSITY')
                ->select('id_university', 'name', 'description', 'state', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get(),
            'careers' => DB::table('CAREER')
                ->select('id_career', 'name', 'description', 'state', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get(),
            'companies' => DB::table('COMPANY')
                ->leftJoin('COUNTRY', 'COUNTRY.id_country', '=', 'COMPANY.id_country')
                ->select(
                    'COMPANY.id_company',
                    'COMPANY.name',
                    'COMPANY.description',
                    'COMPANY.logo_url',
                    'COMPANY.industry',
                    'COMPANY.city',
                    'COMPANY.id_country',
                    'COMPANY.phone_prefix',
                    'COMPANY.phone',
                    'COMPANY.website',
                    'COMPANY.state',
                    'COMPANY.created_at',
                    'COMPANY.updated_at',
                    'COMPANY.banner_url',
                    'COMPANY.mission',
                    'COMPANY.vision',
                    'COUNTRY.name as country_name'
                )
                ->orderBy('COMPANY.name')
                ->get(),
            default => null,
        };

        abort_if($records === null, 404, 'Catalogo no encontrado.');

        return response()->json(['data' => $records]);
    }

    public function areas(): JsonResponse
    {
        $areas = Area::query()
            ->select('id_area', 'name', 'description', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $areas]);
    }

    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->select('id_country', 'name', 'state', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $countries]);
    }
}
