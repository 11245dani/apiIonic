<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Calle;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    protected $base;

    public function __construct()
    {
        $this->base = config('services.api_principal.base_url') ?? env('API_PRINCIPAL_URL');
    }

    // Sincroniza calles: itera paginación y hace updateOrCreate por api_id
    public function syncCalles()
    {
        $page = 1;
        $total = 0;
        do {
            $res = Http::timeout(10)->get($this->base . '/calles', ['page' => $page]);
            if (!$res->successful()) {
                Log::warning("syncCalles fallo en page {$page}: " . $res->body());
                return response()->json(['error'=>'remote failed','page'=>$page], 500);
            }
            $payload = $res->json();
            $items = $payload['data'] ?? $payload;
            foreach ($items as $it) {
                Calle::updateOrCreate(
                    ['api_id' => $it['id'] ?? null],
                    [
                        'nombre' => $it['nombre'] ?? ($it['name'] ?? null),
                        'barrio' => $it['barrio'] ?? null,
                        'meta' => $it
                    ]
                );
                $total++;
            }
            $page++;
            $lastPage = $payload['last_page'] ?? 1;
        } while ($page <= $lastPage);
        return response()->json(['synced' => $total]);
    }

    // Sincroniza vehiculos con paginación
    public function syncVehiculos()
    {
        $page = 1;
        $total = 0;
        do {
            $res = Http::timeout(10)->get($this->base . '/vehiculos', ['page' => $page]);
            if (!$res->successful()) {
                Log::warning("syncVehiculos fallo en page {$page}: " . $res->body());
                return response()->json(['error'=>'remote failed','page'=>$page], 500);
            }
            $payload = $res->json();
            $items = $payload['data'] ?? $payload;
            foreach ($items as $v) {
                Vehiculo::updateOrCreate(
                    ['api_id' => $v['id'] ?? null],
                    [
                        'placa' => $v['placa'] ?? null,
                        'marca' => $v['marca'] ?? null,
                        'modelo' => $v['modelo'] ?? null,
                        'capacidad' => $v['capacidad'] ?? null,
                        'tipo_combustible' => $v['tipo_combustible'] ?? null,
                        'perfil_id' => $v['perfil_id'] ?? null,
                        'activo' => isset($v['activo']) ? (bool)$v['activo'] : true,
                        'api_created_at' => $v['created_at'] ?? null,
                        'api_updated_at' => $v['updated_at'] ?? null,
                        'meta' => $v
                    ]
                );
                $total++;
            }
            $page++;
            $lastPage = $payload['last_page'] ?? 1;
        } while ($page <= $lastPage);
        return response()->json(['synced' => $total]);
    }
}
