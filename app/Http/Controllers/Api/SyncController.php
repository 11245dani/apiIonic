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

    
}
