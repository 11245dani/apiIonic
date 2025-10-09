<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required','confirmed', Rules\Password::defaults()],

            // datos del vehículo
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:10',
            'capacidad' => 'nullable|numeric|min:0',
            'tipo_combustible' => 'nullable|string|max:50',

        ];
    }
}