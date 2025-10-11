<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehiculoRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'placa' => 'required|string|max:10|unique:vehiculos,placa',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:10',
            'capacidad' => 'nullable|numeric|min:0',
            'tipo_combustible' => 'nullable|string|max:50',
            'activo' => 'boolean'
        ];
    }
}
