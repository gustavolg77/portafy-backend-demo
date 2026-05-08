<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'email' => is_string($this->input('email')) ? trim(mb_strtolower($this->input('email'))) : $this->input('email'),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'min:2', 'max:255'],
            'apellido' => [
    Rule::requiredIf($this->role === 'PROFESIONAL'),
    'nullable',
    'string',
    'min:2',
    'max:255',
],
            'email' => ['required', 'email', 'max:255', Rule::unique((new Usuario())->getTable(), 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', 'in:PROFESIONAL,RECLUTADOR'],
        ];

        $recaptchaSecret = (string) config('services.recaptcha.secret_key', '');
        $shouldVerifyRecaptcha = (bool) config('services.recaptcha.verify', ! app()->isLocal());

        if ($recaptchaSecret !== '') {
            $rules['captcha_token'] = [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($recaptchaSecret, $shouldVerifyRecaptcha) {
                    if (! $shouldVerifyRecaptcha) {
                        return;
                    }

                    try {
                        $response = Http::asForm()->timeout(10)->post(
                            'https://www.google.com/recaptcha/api/siteverify',
                            [
                                'secret' => $recaptchaSecret,
                                'response' => $value,
                                'remoteip' => request()->ip(),
                            ]
                        );

                        if (! $response->successful() || ! data_get($response->json(), 'success')) {
                            $fail('El captcha no es valido.');
                        }
                    } catch (\Throwable) {
                        $fail('No se pudo validar el captcha.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Debes enviar un correo valido.',
            'email.unique' => 'Ya existe una cuenta registrada con este correo.',
            'password.required' => 'La contrasena es obligatoria.',
            'password.confirmed' => 'La confirmacion de la contrasena no coincide.',
            'captcha_token.required' => 'Debes completar el captcha.',
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


