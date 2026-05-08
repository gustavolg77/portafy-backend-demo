<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormacionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $current = filter_var(
            $this->input('actualmente', $this->input('is_current', $this->input('isCurrent'))),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->merge([
            'nivel_formacion' => $this->normalizeText($this->input('nivel_formacion', $this->input('tipo_formacion', $this->input('type')))),
            'institucion' => $this->normalizeText($this->input('institucion', $this->input('institution'))),
            'nombre_programa' => $this->normalizeText($this->input('nombre_programa', $this->input('nombre_carrera', $this->input('careerName')))),
            'fecha_inicio' => $this->input('fecha_inicio', $this->input('startDate')),
            'fecha_fin' => $current ? null : $this->input('fecha_fin', $this->input('endDate')),
            'actualmente' => $current,
        ]);
    }

    public function rules(): array
    {
        return [
            'nivel_formacion' => ['nullable', 'in:tecnico,tecnologo,licenciatura,ingenieria,maestria,doctorado,curso,diplomado,otro'],
            'institucion' => ['required', 'string', 'min:2', 'max:200'],
            'nombre_programa' => ['required', 'string', 'min:2', 'max:255'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'actualmente' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }

    public function persistenceData(): array
    {
        return $this->validated();
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
