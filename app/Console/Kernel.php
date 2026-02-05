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

        $schedule->command('leave:accumulation')
            ->everyMinute()
            // ->monthlyOn(1, '0:00') 
            ->onFailure(function () {
                \Log::error('Scheduled job leave:accumulation failed.');
            });
            // ->emailOutputTo('iamcarlllemos@gmail.com');
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
