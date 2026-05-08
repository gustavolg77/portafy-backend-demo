<?php

namespace App\Http\Requests;

use App\Support\ProfileCatalog;
use Illuminate\Foundation\Http\FormRequest;

class SocialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $platform = $this->input('plataforma', $this->input('nombre_plataforma', $this->input('platform')));
        $url = $this->input('url', $this->input('url_plataforma'));

        $this->merge([
            'plataforma' => $this->normalizePlatform($platform),
            'url' => is_string($url) ? trim($url) : $url,
        ]);
    }

    public function rules(): array
    {
        return [
            'plataforma' => ['required', 'in:linkedin,github,gitlab,facebook,instagram,x,youtube,tiktok,behance,dribbble,medium,stackoverflow,portafolio,otro'],
            'url' => ['nullable', 'url', 'max:255', 'required_with:plataforma'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! filled($this->input('plataforma')) || ! filled($this->input('url'))) {
                $validator->errors()->add('plataforma', 'Debes enviar una plataforma y su enlace.');
            }
        });
    }

    public function persistenceData(): array
    {
        return $this->validated();
    }

    private function normalizePlatform(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return ProfileCatalog::normalizePlatform($value);
    }
}
