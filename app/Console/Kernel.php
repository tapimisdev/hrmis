<?php

namespace App\Console;

use App\Jobs\CheckAllEmployeeViolations;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inspire')->hourly();
        $schedule->command('recruitment:close-expired-applicants')->dailyAt('01:00');

        // $schedule->command('leave:accumulation')
        //     ->everyMinute()
        //     // ->monthlyOn(1, '0:00') 
        //     ->onFailure(function () {
        //         \Log::error('Scheduled job leave:accumulation failed.');
        //     });
            // ->emailOutputTo('iamcarlllemos@gmail.com');

        // Use month numbers (for example, [1, 2]) or specific periods (for example, ['2026-01']).
        $violationMonthExemptions = [];

        $schedule->call(fn () => (new CheckAllEmployeeViolations(
            now()->year,
            $violationMonthExemptions
        ))->handle())
            ->twiceDaily(0, 12)
            ->name('check-all-employee-violations')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Scheduled job CheckAllEmployeeViolations failed.');
            });
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
