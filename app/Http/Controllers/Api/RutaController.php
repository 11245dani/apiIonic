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
    /**
     * Crear una ruta en la API principal y guardar localmente
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nombre_ruta' => 'required|string|max:100',
            'calles' => 'required|array|min:1'
        ]);

        $user = User::find($request->user_id);
        $perfil_id = $user->perfil_id ?? null;

        if (!$perfil_id) {
            return response()->json(['message' => 'El usuario no tiene perfil_id asociado'], 400);
        }

        try {
            // Enviar datos a la API principal
            $response = Http::withoutVerifying()
                ->post('http://apirecoleccion.gonzaloandreslucio.com/api/rutas', [
                    'nombre_ruta' => $request->nombre_ruta,
                    'calles' => $request->calles,
                    'perfil_id' => $perfil_id
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Guardar localmente
                $ruta = Ruta::create([
                    'api_id' => $data['id'] ?? null,
                    'user_id' => $user->id,
                    'perfil_id' => $perfil_id,
                    'nombre_ruta' => $data['nombre_ruta'] ?? $request->nombre_ruta,
                    'calles' => $data['calles'] ?? $request->calles,
                    'shape' => $data['shape'] ?? null,
                    'sincronizado' => true,
                ]);

                return response()->json([
                    'message' => 'Ruta creada correctamente en API principal y guardada localmente',
                    'ruta' => $ruta
                ], 201);
            }

            return response()->json([
                'message' => 'Error al crear la ruta en la API principal',
                'error' => $response->body()
            ], $response->status());
        } catch (\Exception $e) {
            // En caso de error, guardar localmente para sincronizar después
            $ruta = Ruta::create([
                'user_id' => $user->id,
                'perfil_id' => $perfil_id,
                'nombre_ruta' => $request->nombre_ruta,
                'calles' => $request->calles,
                'sincronizado' => false,
            ]);

            Log::error('Error al conectar con API principal', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ruta guardada localmente (sin sincronizar)',
                'ruta' => $ruta,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener rutas desde la API principal y sincronizarlas localmente
     */
    public function syncFromPrincipal(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $perfil_id = $user->perfil_id ?? null;

        if (!$perfil_id) {
            return response()->json(['message' => 'El usuario no tiene perfil_id asociado'], 400);
        }

        try {
            $response = Http::withoutVerifying()
                ->get('http://apirecoleccion.gonzaloandreslucio.com/api/rutas', [
                    'perfil_id' => $perfil_id
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Error al obtener rutas desde API principal',
                    'error' => $response->body(),
                ], $response->status());
            }

            $data = $response->json();
            $rutasGuardadas = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $rutaData) {
                    $ruta = Ruta::updateOrCreate(
                        ['api_id' => $rutaData['id']],
                        [
                            'user_id' => $user->id,
                            'perfil_id' => $perfil_id,
                            'nombre_ruta' => $rutaData['nombre_ruta'] ?? '',
                            'calles' => $rutaData['calles'] ?? [],
                            'shape' => $rutaData['shape'] ?? null,
                            'sincronizado' => true,
                        ]
                    );
                    $rutasGuardadas[] = $ruta;
                }
            }

            return response()->json([
                'message' => 'Rutas sincronizadas correctamente desde la API principal',
                'cantidad' => count($rutasGuardadas),
                'calles' => $data,
                'perfil_id' => $perfil_id,
                'rutas_guardadas' => $rutasGuardadas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al sincronizar rutas',
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
