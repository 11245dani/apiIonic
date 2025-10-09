<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\User;

class VehiculoController extends Controller
{
    // Obtener vehículo asociado a un usuario
    public function showByUser($userId)
    {
        // Verifica que el usuario exista
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Busca el vehículo asignado (relación directa o campo user_id)
        $vehiculo = Vehiculo::where('user_id', $userId)->first();

        if (!$vehiculo) {
            return response()->json(['message' => 'No hay vehículo asignado a este usuario'], 404);
        }

        return response()->json([
            'message' => 'Vehículo encontrado',
            'vehiculo' => $vehiculo
        ]);
    }
}
