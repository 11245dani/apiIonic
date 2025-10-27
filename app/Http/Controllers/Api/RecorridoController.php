<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recorrido;
use App\Models\Posicion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RecorridoController extends Controller
{
    // ===========================================================
    // 🔹 Obtener recorridos del usuario desde la API principal
    // ===========================================================
    public function misRecorridos(Request $request)
    {
        $user = $request->user();

        if (!$user->perfil_id) {
            return response()->json(['message' => 'El usuario no tiene perfil_id'], 400);
        }

        try {
            $response = Http::withoutVerifying()
                ->get('http://apirecoleccion.gonzaloandreslucio.com/api/misrecorridos', [
                    'perfil_id' => $user->perfil_id
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Error al obtener recorridos desde API principal',
                    'error' => $response->body(),
                ], $response->status());
            }

            $data = $response->json()['data'] ?? [];
            $recorridosGuardados = [];

            foreach ($data as $recRemoto) {
                $recLocal = Recorrido::updateOrCreate(
                    ['api_id' => $recRemoto['id']],
                    [
                        'ruta_id' => $recRemoto['ruta_id'] ?? null,
                        'vehiculo_id' => $recRemoto['vehiculo_id'] ?? null,
                        'perfil_id' => $recRemoto['perfil_id'] ?? null,
                        'user_id' => $user->id,
                        'estado' => strtolower($recRemoto['estado']) == 'completado' ? 'finalizado' : 'en_curso',
                    ]
                );
                $recorridosGuardados[] = $recLocal;
            }

            return response()->json([
                'message' => 'Recorridos sincronizados correctamente',
                'recorridos' => $recorridosGuardados,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener recorridos desde API principal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ===========================================================
    // 🔹 Iniciar recorrido
    // ===========================================================
    public function iniciar(Request $request)
    {
        $data = $request->validate([
            'ruta_id' => 'required|uuid',
            'vehiculo_id' => 'required|uuid',
            'perfil_id' => 'required|uuid',
        ]);

        $user = $request->user();

        $recorrido = Recorrido::create([
            'api_id' => null,
            'ruta_id' => $data['ruta_id'],
            'vehiculo_id' => $data['vehiculo_id'],
            'perfil_id' => $data['perfil_id'],
            'user_id' => $user->id,
            'estado' => 'en_curso'
        ]);

        $res = Http::withoutVerifying()
            ->post("http://apirecoleccion.gonzaloandreslucio.com/api/recorridos/iniciar", $data);

        if ($res->successful()) {
            $api = $res->json();
            $recorrido->update(['api_id' => $api['id'] ?? null]);
        }

        return response()->json([
            'message' => 'Recorrido iniciado correctamente',
            'recorrido' => $recorrido,
        ]);
    }

    // ===========================================================
    // 🔹 Registrar posición
    // ===========================================================
    public function registrarPosicion(Request $request, $recorrido)
    {
        $data = $request->validate([
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
        ]);

        $recorrido = Recorrido::where('id', $recorrido)
            ->orWhere('api_id', $recorrido)
            ->firstOrFail();

        $pos = Posicion::create([
            'recorrido_id' => $recorrido->id,
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud'],
        ]);

        if ($recorrido->api_id) {
            Http::withoutVerifying()->post(
                "http://apirecoleccion.gonzaloandreslucio.com/api/recorridos/{$recorrido->api_id}/posiciones",
                [
                    'lat' => $data['latitud'],
                    'lon' => $data['longitud'],
                    'perfil_id' => $recorrido->perfil_id,
                ]
            );
        }

    return response()->json([
    'message' => 'Posición registrada correctamente',
    'posicion' => [
        'recorrido_id' => $recorrido->api_id ?? $recorrido->id, // 👈 usa el de la API principal si existe
        'latitud' => $pos->latitud,
        'longitud' => $pos->longitud,
        'created_at' => $pos->created_at,
        'updated_at' => $pos->updated_at,
    ],
]);

    }

    // ===========================================================
    // 🔹 Obtener posiciones
    // ===========================================================
    public function obtenerPosiciones(Request $request, $recorrido)
    {
        $recorrido = Recorrido::where('id', $recorrido)
            ->orWhere('api_id', $recorrido)
            ->firstOrFail();

        $response = Http::withoutVerifying()
            ->get("http://apirecoleccion.gonzaloandreslucio.com/api/recorridos/{$recorrido->api_id}/posiciones", [
                'perfil_id' => $recorrido->perfil_id,
            ]);

        return response()->json($response->json());
    }

    // ===========================================================
    // 🔹 Finalizar recorrido
    // ===========================================================
    public function finalizar(Request $request, $recorrido)
    {
        $recorrido = Recorrido::where('id', $recorrido)
            ->orWhere('api_id', $recorrido)
            ->firstOrFail();

        if ($recorrido->api_id) {
            Http::withoutVerifying()->post(
                "http://apirecoleccion.gonzaloandreslucio.com/api/recorridos/{$recorrido->api_id}/finalizar",
                ['perfil_id' => $recorrido->perfil_id]
            );
        }

        $recorrido->update(['estado' => 'finalizado']);

        return response()->json([
            'message' => 'Recorrido finalizado correctamente',
            'recorrido' => $recorrido
        ]);
    }

    // ===========================================================
    // 🔹 Sincronizar estados locales con API principal
    // ===========================================================
    public function sincronizarEstados(Request $request)
    {
        $user = $request->user();

        if (!$user->perfil_id) {
            return response()->json(['message' => 'El usuario no tiene perfil_id asociado'], 400);
        }

        $recorridos = Recorrido::where('user_id', $user->id)->get();
        $actualizados = 0;

        foreach ($recorridos as $recorrido) {
            if (!$recorrido->api_id) continue;

            $response = Http::withoutVerifying()
                ->get("http://apirecoleccion.gonzaloandreslucio.com/api/recorridos/{$recorrido->api_id}", [
                    'perfil_id' => $user->perfil_id,
                ]);

            if (!$response->successful()) continue;

            $remoto = $response->json()['data'] ?? null;
            if (!$remoto) continue;

            $estadoRemoto = strtolower($remoto['estado']);
            $estadoLocal = $estadoRemoto === 'completado' ? 'finalizado' : 'en_curso';

            if ($recorrido->estado !== $estadoLocal) {
                $recorrido->update(['estado' => $estadoLocal]);
                $actualizados++;
            }
        }

        return response()->json([
            'message' => "Sincronización completada: {$actualizados} recorridos actualizados.",
            'cantidad' => $actualizados
        ]);
    }
}
