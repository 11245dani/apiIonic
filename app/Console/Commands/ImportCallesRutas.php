<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Ruta;

class ImportCallesRutas extends Command
{
    protected $signature = 'import:calles-rutas {user_id} {path=response_1761575454787.json} {--grouped}';
    protected $description = 'Crea rutas individuales o agrupadas a partir de un archivo de calles.';

    public function handle()
    {
        $user = User::find($this->argument('user_id'));

        if (!$user || !$user->perfil_id) {
            $this->error('❌ El usuario no existe o no tiene perfil_id asociado.');
            return;
        }

        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("❌ No se encontró el archivo: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        $calles = $data['data'] ?? [];

        if (empty($calles)) {
            $this->error('⚠️ No se encontraron calles en el archivo.');
            return;
        }

        $this->info("📄 Se encontraron " . count($calles) . " calles en el archivo.");

        // Elegir modo
        if ($this->option('grouped')) {
            $this->createGroupedRoutes($user, $calles);
        } else {
            $this->createIndividualRoutes($user, $calles);
        }
    }

    /**
     * 🔹 Versión A: Crear una ruta por cada calle individual
     */
    private function createIndividualRoutes(User $user, array $calles)
    {
        foreach ($calles as $calle) {
            try {
                $payload = [
                    'nombre_ruta' => $calle['nombre'] ?? 'Ruta sin nombre',
                    'perfil_id' => $user->perfil_id,
                    'shape' => $calle['shape'] ?? null,
                ];

                $response = Http::withoutVerifying()
                    ->post('http://apirecoleccion.gonzaloandreslucio.com/api/rutas', $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    Ruta::create([
                        'api_id' => $data['id'] ?? null,
                        'user_id' => $user->id,
                        'perfil_id' => $user->perfil_id,
                        'nombre_ruta' => $data['nombre_ruta'] ?? $calle['nombre'],
                        'shape' => $data['shape'] ?? $calle['shape'],
                        'calles' => $data['calles'] ?? [],
                        'sincronizado' => true,
                    ]);

                    $this->info("✅ Ruta creada: {$calle['nombre']}");
                } else {
                    $this->warn("⚠️ Error en {$calle['nombre']}: " . $response->body());
                }
            } catch (\Exception $e) {
                $this->error("❌ Excepción en {$calle['nombre']}: " . $e->getMessage());
            }
        }

        $this->info('🚀 Rutas individuales creadas correctamente.');
    }

    /**
     * 🔹 Versión B: Crear una ruta agrupando calles por nombre
     */
    private function createGroupedRoutes(User $user, array $calles)
{
    $agrupadas = collect($calles)->groupBy('nombre');
    $this->info("📦 Se crearán " . count($agrupadas) . " rutas agrupadas por nombre...");

    foreach ($agrupadas as $nombre => $grupo) {
        try {
            $calles_ids = collect($grupo)->pluck('id')->values()->toArray();

            $payload = [
                'nombre_ruta' => "Ruta {$nombre}",
                'perfil_id' => $user->perfil_id,
                'calles_ids' => $calles_ids,
            ];

            // 👇 IMPORTANTE: forzar a JSON para evitar “Array to string conversion”
            $response = Http::withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->send('POST', 'http://apirecoleccion.gonzaloandreslucio.com/api/rutas', [
                    'body' => json_encode($payload),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Ruta::create([
                    'api_id' => $data['id'] ?? null,
                    'user_id' => $user->id,
                    'perfil_id' => $user->perfil_id,
                    'nombre_ruta' => $data['nombre_ruta'] ?? "Ruta {$nombre}",
                    'shape' => $data['shape'] ?? null,
                    'calles' => $data['calles'] ?? [],
                    'sincronizado' => true,
                ]);

                $this->info("✅ Ruta agrupada creada: Ruta {$nombre} (" . count($calles_ids) . " calles)");
            } else {
                $this->warn("⚠️ Error creando Ruta {$nombre}: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción en Ruta {$nombre}: " . $e->getMessage());
        }
    }

    $this->info('🚀 Rutas agrupadas creadas correctamente.');
}

}
