<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehiculo;

class VehiculoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'placa'   => 'required|string|max:10',
            'marca'   => 'required|string|max:50',
            'modelo'  => 'required|string|max:10',
            'activo'  => 'required|boolean',
        ]);

        $user = User::find($request->user_id);
        $perfil_id = $user->perfil_id ?? null;

        if (!$perfil_id) {
            return response()->json([
                'message' => 'El usuario no tiene perfil_id asociado'
            ], 400);
        }

        try {
            Log::info('Datos enviados a API principal', [
                'placa'     => $request->placa,
                'marca'     => $request->marca,
                'modelo'    => $request->modelo,
                'activo'    => $request->activo,
                'perfil_id' => $perfil_id,
            ]);

            $apiResponse = Http::withoutVerifying()
                ->withHeaders(['Accept' => 'application/json'])
                ->post('http://apirecoleccion.gonzaloandreslucio.com/api/vehiculos', [
                    'placa'      => $request->placa,
                    'marca'      => $request->marca,
                    'modelo'     => $request->modelo,
                    'activo'     => $request->activo,
                    'perfil_id'  => $perfil_id,
                ]);

            Log::info('Respuesta API principal completa', [
                'status' => $apiResponse->status(),
                'body'   => $apiResponse->body(),
            ]);

            if ($apiResponse->successful()) {
                return response()->json([
                    'message' => 'Vehículo creado exitosamente en la API principal',
                    'vehiculo' => $apiResponse->json(),
                ], 201);
            }

            Log::error('Error API principal', [
                'status' => $apiResponse->status(),
                'body'   => $apiResponse->body(),
            ]);

            return response()->json([
                'message' => 'Error al crear el vehículo en la API principal',
                'error'   => $apiResponse->body()
            ], $apiResponse->status());

        } catch (\Exception $e) {
            Log::error('Excepción al conectar con API principal', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo conectar con la API principal',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $response = Http::withoutVerifying()->get('http://apirecoleccion.gonzaloandreslucio.com/api/vehiculos');

            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['message' => 'Error al obtener datos de la API principal'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error de conexión: ' . $e->getMessage()], 500);
        }
    }

    public function syncFromPrincipal(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $perfil_id = $user->perfil_id ?? null;

        if (!$perfil_id) {
            return response()->json([
                'message' => 'El usuario no tiene perfil_id asociado'
            ], 400);
        }

        try {
            // ✅ 1. Obtener vehículos desde la API principal
            $response = Http::withoutVerifying()->get(
                'http://apirecoleccion.gonzaloandreslucio.com/api/vehiculos',
                ['perfil_id' => $perfil_id]
            );

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Error al obtener vehículos desde la API principal',
                    'error'   => $response->body(),
                ], $response->status());
            }

            $data = $response->json();

            // ✅ 2. Validar formato de datos
            if (!isset($data['data']) || !is_array($data['data'])) {
                return response()->json([
                    'message' => 'Formato inesperado en respuesta de la API principal',
                    'data' => $data
                ], 500);
            }

            $vehiculos = $data['data'];
            $vehiculosGuardados = [];
            $count = 0;

            // ✅ 3. Guardar/actualizar localmente y agregar a lista
            foreach ($vehiculos as $vehiculoData) {
                $vehiculo = Vehiculo::updateOrCreate(
                    ['placa' => $vehiculoData['placa']],
                    [ 
                        'api_id'     => $vehiculoData['id'],
                        'user_id'    => $user->id,
                        'marca'      => $vehiculoData['marca'],
                        'modelo'     => $vehiculoData['modelo'],
                        'activo'     => $vehiculoData['activo'],
                        'perfil_id'  => $vehiculoData['perfil_id'],
                        'created_at' => $vehiculoData['created_at'],
                        'updated_at' => $vehiculoData['updated_at'],
                    ]
                );

                $vehiculo->sincronizado = true;
                $vehiculo->save();

                $vehiculosGuardados[] = $vehiculo;
                $count++;
            }

            // ✅ 4. Respuesta final completa
            return response()->json([
                'message' => 'Vehículos sincronizados correctamente desde la API principal',
                'cantidad' => $count,
                'perfil_id' => $perfil_id,
                'vehiculos_guardados' => $vehiculosGuardados
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al sincronizar vehículos desde API principal', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al sincronizar vehículos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

  public function destroy($id)
{
    try {
        // ✅ Buscar vehículo local por ID numérico o UUID API
        $vehiculo = Vehiculo::where('id', $id)
            ->orWhere('api_id', $id)
            ->first();

        if (!$vehiculo) {
            return response()->json([
                'message' => 'Vehículo no encontrado en el sistema local'
            ], 404);
        }

        // ✅ Determinar cuál ID usar para la API principal
        $apiId = $vehiculo->api_id ?? $id;

        Log::info('Intentando eliminar vehículo', [
            'local_id' => $vehiculo->id,
            'api_id' => $apiId,
            'placa' => $vehiculo->placa
        ]);

        // 🔹 Eliminar en API principal
        $apiResponse = Http::withoutVerifying()
            ->withHeaders(['Accept' => 'application/json'])
            ->delete("http://apirecoleccion.gonzaloandreslucio.com/api/vehiculos/{$apiId}");

        Log::info('Respuesta eliminación API principal', [
            'status' => $apiResponse->status(),
            'body'   => $apiResponse->body(),
        ]);

        // 🔹 Eliminar localmente
        $vehiculo->delete();

        return response()->json([
            'message' => 'Vehículo eliminado correctamente (local y API principal)',
            'id_local' => $vehiculo->id,
            'id_api' => $apiId,
            'api_status' => $apiResponse->status(),
            'api_respuesta' => $apiResponse->json()
        ]);

    } catch (\Exception $e) {
        Log::error('Error al eliminar vehículo', [
            'id' => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Error al eliminar vehículo',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}