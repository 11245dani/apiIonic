<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calle;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class LocalDataController extends Controller
{
    // GET /api/calles -> paginado
    public function calles(Request $request)
    {
        $per = (int) $request->get('per_page', 50);
        $q = Calle::query();

        if ($request->filled('q')) {
            $qStr = $request->get('q');
            $q->where('nombre', 'like', "%{$qStr}%")->orWhere('barrio','like',"%{$qStr}%");
        }

        return $q->orderBy('nombre')->paginate($per);
    }

    // GET /api/vehiculos -> paginado filtro opcional por perfil_id o placa
    public function vehiculos(Request $request)
    {
        $per = (int) $request->get('per_page', 15);
        $q = Vehiculo::query();

        if ($request->filled('perfil_id')) {
            $q->where('perfil_id', $request->get('perfil_id'));
        }

        if ($request->filled('placa')) {
            $q->where('placa', $request->get('placa'));
        }

        return $q->orderBy('id','desc')->paginate($per);
    }

    // GET /api/vehiculos/usuario/{userId}
    public function vehiculoPorUsuario($userId)
    {
        $veh = Vehiculo::where('user_id', $userId)->first();
        if (!$veh) {
            return response()->json(null, 404);
        }
        return response()->json($veh);
    }
}
