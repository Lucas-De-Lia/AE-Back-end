<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        $schedule->call(function () {
            // Calcula el tiempo hace 5 minutos
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);

            // Realiza la eliminación de registros según la condición
            DB::table('email_to_verify')
                ->where('created_at', '<=', $fiveMinutesAgo)
                ->delete();
        })->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
