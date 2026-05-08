<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->normalizeText($this->input('nombre')),
            'apellido' => $this->normalizeText($this->input('apellido')),
            'profesion' => $this->normalizeText($this->input('profesion')),
            'biografia' => $this->normalizeText($this->input('biografia')),
            'ubicacion' => $this->normalizeText($this->input('ubicacion')),
            'visibilidad_contacto' => is_string($this->input('visibilidad_contacto')) ? trim($this->input('visibilidad_contacto')) : $this->input('visibilidad_contacto'),
            'github' => is_string($this->input('github')) ? trim($this->input('github')) : $this->input('github'),
            'linkedin' => is_string($this->input('linkedin')) ? trim($this->input('linkedin')) : $this->input('linkedin'),
        ]);
    }

    public function rules(): array
    {
        return [
            'foto_perfil' => ['nullable', 'image', 'max:2048'],
            'foto_portada' => ['nullable', 'image', 'max:2048'],
            'profesion' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'biografia' => ['nullable', 'string', 'max:1000'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'visibilidad_contacto' => ['nullable', 'in:public,private'],
            'contacto_publico' => ['nullable', 'boolean'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'github' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto_perfil.image' => 'La foto de perfil debe ser una imagen valida.',
            'foto_perfil.max' => 'La foto de perfil supera el limite actual de 2 MB del servidor.',
            'foto_portada.image' => 'La portada debe ser una imagen valida.',
            'foto_portada.max' => 'La portada supera el limite actual de 2 MB del servidor.',
            'github.url' => 'El enlace de GitHub debe ser un URL valido.',
            'linkedin.url' => 'El enlace de LinkedIn debe ser un URL valido.',
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($value));
    }
}
