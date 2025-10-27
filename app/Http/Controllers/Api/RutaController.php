<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ruta;

class RutaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nombre_ruta' => 'required|string|max:100',
            'shape' => 'nullable',
            'calles_ids' => 'nullable|array|min:1'
        ]);

        if (!$request->filled('shape') && !$request->filled('calles_ids')) {
            return response()->json([
                'message' => 'Debes enviar al menos "shape" o "calles_ids".'
            ], 400);
        }

        $user = User::find($request->user_id);
        $perfil_id = $user->perfil_id ?? null;

        if (!$perfil_id) {
            return response()->json(['message' => 'El usuario no tiene perfil_id asociado'], 400);
        }

        try {
            // 🛰 Enviar datos al servidor principal
            $payload = [
                'nombre_ruta' => $request->nombre_ruta,
                'perfil_id' => $perfil_id,
            ];

            if ($request->filled('shape')) {
                $payload['shape'] = is_string($request->shape)
                    ? $request->shape
                    : json_encode($request->shape);
            } elseif ($request->filled('calles_ids')) {
                $payload['calles_ids'] = $request->calles_ids;
            }

            $response = Http::withoutVerifying()
                ->post('http://apirecoleccion.gonzaloandreslucio.com/api/rutas', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $ruta = Ruta::create([
                    'api_id' => $data['id'] ?? null,
                    'user_id' => $user->id,
                    'perfil_id' => $perfil_id,
                    'nombre_ruta' => $data['nombre_ruta'] ?? $request->nombre_ruta,
                    'calles' => $data['calles'] ?? [],
                    'shape' => $data['shape'] ?? $request->shape,
                    'sincronizado' => true,
                ]);

                return response()->json([
                    'message' => 'Ruta creada correctamente en la API principal y guardada localmente',
                    'ruta' => $ruta
                ], 201);
            }

            return response()->json([
                'message' => 'Error al crear la ruta en la API principal',
                'error' => $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Error al conectar con API principal', ['error' => $e->getMessage()]);

            $ruta = Ruta::create([
                'user_id' => $user->id,
                'perfil_id' => $perfil_id,
                'nombre_ruta' => $request->nombre_ruta,
                'calles' => [],
                'shape' => $request->shape,
                'sincronizado' => false,
            ]);

            return response()->json([
                'message' => 'Ruta guardada localmente (sin sincronizar)',
                'ruta' => $ruta,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===============================================================
// ============= SINCRONIZAR RUTAS DESDE API PRINCIPAL ============
// ===============================================================
public function syncFromPrincipal()
{
    try {
        // Obtener todos los usuarios con perfil vinculado
        $usuarios = \App\Models\User::whereNotNull('perfil_id')->get();

        if ($usuarios->isEmpty()) {
            return response()->json(['message' => 'No hay usuarios con perfil_id para sincronizar'], 404);
        }

        $totalSincronizadas = 0;
        $rutasGuardadas = [];

        foreach ($usuarios as $user) {
            $perfil_id = $user->perfil_id;

            // Llamada a la API principal
            $response = Http::withoutVerifying()
                ->get("http://apirecoleccion.gonzaloandreslucio.com/api/rutas/perfil/{$perfil_id}");

            if (!$response->successful()) {
                \Log::warning("Fallo sincronizando rutas para perfil {$perfil_id}: " . $response->body());
                continue;
            }

            $data = $response->json();

            if (!isset($data['rutas']) || !is_array($data['rutas'])) {
                \Log::warning("Respuesta inválida al sincronizar perfil {$perfil_id}: " . json_encode($data));
                continue;
            }

            foreach ($data['rutas'] as $rutaRemota) {
                // Buscar si ya existe localmente
                $rutaLocal = \App\Models\Ruta::where('api_id', $rutaRemota['id'])->first();

                if ($rutaLocal) {
                    $rutaLocal->update([
                        'nombre_ruta' => $rutaRemota['nombre_ruta'] ?? $rutaLocal->nombre_ruta,
                        'calles' => $rutaRemota['calles'] ?? [],
                        'shape' => $rutaRemota['shape'] ?? null,
                        'sincronizado' => true,
                    ]);
                } else {
                    $rutaLocal = \App\Models\Ruta::create([
                        'api_id' => $rutaRemota['id'] ?? null,
                        'user_id' => $user->id,
                        'perfil_id' => $perfil_id,
                        'nombre_ruta' => $rutaRemota['nombre_ruta'] ?? 'Sin nombre',
                        'calles' => $rutaRemota['calles'] ?? [],
                        'shape' => $rutaRemota['shape'] ?? null,
                        'sincronizado' => true,
                    ]);
                }

                $rutasGuardadas[] = $rutaLocal;
                $totalSincronizadas++;
            }
        }

        return response()->json([
            'message' => 'Rutas sincronizadas correctamente desde la API principal',
            'cantidad' => $totalSincronizadas,
            'rutas' => $rutasGuardadas
        ]);
    } catch (\Exception $e) {
        \Log::error('Error al sincronizar rutas desde API principal', ['error' => $e->getMessage()]);

        return response()->json([
            'message' => 'Error al sincronizar rutas desde API principal',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Listar rutas locales
     */
    public function index()
    {
        return response()->json(Ruta::all());
    }
}
