<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Los comandos de consola registrados manualmente.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SyncRutasToApiPrincipal::class,
    ];

    /**
     * Define la programación de tareas.
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Registra los comandos para la aplicación.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
