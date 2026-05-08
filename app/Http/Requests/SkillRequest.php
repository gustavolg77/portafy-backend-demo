<?php

namespace App\Http\Requests;

use App\Models\Habilidad;
use Illuminate\Foundation\Http\FormRequest;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $texto = $this->resolveNivelTexto();
        $numero = $this->resolveNivelNumero();

        $this->merge([
            'nombre' => $this->normalizeText($this->input('nombre', $this->input('name'))),
            'descripcion' => $this->normalizeText($this->input('descripcion', $this->input('description'))),
            'tipo' => $this->normalizeTipo($this->input('tipo', $this->input('category', 'tecnica'))),
            'nivel_texto' => $texto,
            'nivel_numero' => $numero,
            'nivel' => $this->resolveNivelLegacy($numero),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $userId = $this->user()?->getKey();

                    if (! $userId || ! is_string($value)) {
                        return;
                    }

                    $query = Habilidad::forUser($userId)
                        ->whereHas('skill', fn ($skillQuery) => $skillQuery->where('name', trim($value)));

                    if ($this->route('id')) {
                        $query->whereKeyNot($this->route('id'));
                    }

                    if ($query->exists()) {
                        $fail('Ya registraste esta habilidad.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'tipo' => ['required', 'in:tecnica,blanda'],
            'nivel_texto' => ['nullable', 'in:basico,intermedio,avanzado,experto'],
            'nivel_numero' => ['nullable', 'integer', 'min:1', 'max:100'],
            'nivel' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya registraste esta habilidad.',
        ];
    }

    private function resolveNivelTexto(): ?string
    {
        $value = $this->input('nivel_texto', $this->input('nivel_cuantitativo', $this->input('level')));

        return match ($value) {
            'Junior' => 'basico',
            'Mid' => 'intermedio',
            'Senior' => 'avanzado',
            default => is_string($value) ? mb_strtolower(trim($value)) : null,
        };
    }

    private function resolveNivelNumero(): ?int
    {
        $value = $this->input('nivel_numero', $this->input('nivel_cualitativo'));

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return match ($this->input('level')) {
            'Junior' => 30,
            'Mid' => 60,
            'Senior' => 85,
            default => null,
        };
    }

    private function resolveNivelLegacy(?int $numero): ?int
    {
        $legacy = $this->input('nivel');

        if ($legacy !== null && $legacy !== '') {
            return max(1, min(5, (int) $legacy));
        }

        if ($numero === null) {
            return match ($this->input('level')) {
                'Senior' => 4,
                'Mid' => 3,
                'Junior' => 2,
                default => null,
            };
        }

        if ($numero >= 80) return 4;
        if ($numero >= 50) return 3;
        if ($numero > 0) return 2;

        return null;
    }

    public function persistenceData(): array
    {
        return $this->validated();
    }

    private function normalizeTipo(mixed $value): string
    {
        $value = is_string($value) ? mb_strtolower(trim($value)) : 'tecnica';

        return match ($value) {
            'blandas', 'blanda' => 'blanda',
            default => 'tecnica',
        };
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
