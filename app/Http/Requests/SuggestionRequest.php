<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'administrator_profile_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'in:agregar,idea,mejora,eliminar'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de sugerencia debe ser: agregar, idea, mejora o eliminar.',
            'date_from.date' => 'La fecha inicial debe ser una fecha válida.',
            'date_to.date' => 'La fecha final debe ser una fecha válida.',
        ];
    }
}