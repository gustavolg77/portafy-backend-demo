<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'active_category' => ['nullable', 'string', 'max:50'],

            'filters' => ['nullable', 'array'],
            'filters.ubicacion' => ['nullable', 'string', 'max:150'],
            'filters.profesion' => ['nullable', 'string', 'max:150'],
            'filters.habilidad' => ['nullable', 'string', 'max:150'],
            'filters.hab_tipo' => ['nullable', 'string', 'in:tecnica,blanda'],
            'filters.exp_cargo' => ['nullable', 'string', 'max:150'],
            'filters.exp_empresa' => ['nullable', 'string', 'max:200'],
            'filters.institucion' => ['nullable', 'string', 'max:200'],
            'filters.nivel_formacion' => ['nullable', 'string', 'max:100'],

            'usuario' => ['nullable', 'array'],
            'usuario.ubicacion' => ['nullable', 'string', 'max:150'],
            'usuario.profesion' => ['nullable', 'string', 'max:150'],

            'habilidades' => ['nullable', 'array'],
            'habilidades.nombre' => ['nullable', 'string', 'max:150'],
            'habilidades.tipo' => ['nullable', 'string', 'in:tecnica,blanda'],

            'experiencias' => ['nullable', 'array'],
            'experiencias.cargo' => ['nullable', 'string', 'max:150'],
            'experiencias.empresa' => ['nullable', 'string', 'max:200'],

            'formaciones' => ['nullable', 'array'],
            'formaciones.institucion' => ['nullable', 'string', 'max:200'],
            'formaciones.nivel_formacion' => ['nullable', 'string', 'max:100'],
        ];
    }
}
