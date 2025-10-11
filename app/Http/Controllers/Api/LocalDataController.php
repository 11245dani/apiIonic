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
    
}
