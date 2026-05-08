<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {

        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'                => ['required', 'string', 'max:255'],
            'apellido'              => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:USER,email'],
            'numero'                => ['nullable', 'integer'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'            => 'El nombre es obligatorio.',
            'apellido.required'          => 'El apellido es obligatorio.',
            'email.required'             => 'El correo es obligatorio.',
            'email.email'                => 'El correo no tiene un formato válido.',
            'email.unique'               => 'El correo ya está registrado.',
            'numero.integer'             => 'El número debe ser entero.',
            'password.required'          => 'La contraseña es obligatoria.',
            'password.min'               => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'         => 'Las contraseñas no coinciden.',
        ];
    }
}
