<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|min:2|max:255',
            'description'  => 'required|string|min:20|max:500',
            'industry'     => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'country_name' => 'required|string|exists:COUNTRY,name',
            'phone_prefix' => 'required|string|max:10',
            'phone'        => 'required|string|min:7|max:30',
            'website'      => 'nullable|url|max:255',
            'logo'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
