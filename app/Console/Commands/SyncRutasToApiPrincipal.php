<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Ruta;
use App\Models\User;

class SyncRutasToApiPrincipal extends Command
{
    protected $signature = 'sync:rutas-to-api';
    protected $description = 'Sincroniza rutas locales sin api_id hacia la API principal y actualiza sus UUID.';

    public function handle()
    {
        $rutas = Ruta::whereNull('api_id')->get();

        if ($rutas->isEmpty()) {
            $this->info('✅ No hay rutas pendientes por sincronizar.');
            return;
        }

        $this->info("📦 Se encontraron {$rutas->count()} rutas locales sin api_id...");

        foreach ($rutas as $ruta) {
                            try {
                    $user = User::find($ruta->user_id);

                    if (!$user || !$user->perfil_id) {
                        $this->warn("⚠️ Ruta {$ruta->id} sin usuario o perfil asociado. Saltando...");
                        continue;
                    }

                    $payload = [
                        'nombre_ruta' => $ruta->nombre_ruta,
                        'perfil_id' => $user->perfil_id,
                    ];

                    if ($ruta->shape) {
                        $payload['shape'] = is_string($ruta->shape)
                            ? json_decode($ruta->shape, true)
                            : $ruta->shape;
                    }

                    $this->info("➡️ Enviando {$ruta->nombre_ruta} a API principal...");

                    $res = Http::withoutVerifying()
                        ->timeout(30)
                        ->post('http://apirecoleccion.gonzaloandreslucio.com/api/rutas', $payload);

                    // Mostrar la respuesta completa
                    $this->line('🔍 Respuesta API: ' . $res->body());

if ($res->successful()) {
    $data = $res->json();

    // Accedemos correctamente al ID dentro de "data"
    $remoteId = isset($data['data']['id']) ? $data['data']['id'] : null;

    $ruta->update([
        'api_id' => $remoteId,
        'sincronizado' => true,
    ]);

    $this->info("✅ Sincronizada: {$ruta->nombre_ruta} (ID remoto: {$remoteId})");
}
 else {
                            $this->warn("⚠️ Error en {$ruta->nombre_ruta}: Código HTTP {$res->status()} - " . $res->body());
                        }

                } catch (\Exception $e) {
                    $this->error("❌ Excepción en ruta {$ruta->id}: " . $e->getMessage());
                }
            }

        $this->info('🚀 Sincronización de rutas completada.');
    }
}
