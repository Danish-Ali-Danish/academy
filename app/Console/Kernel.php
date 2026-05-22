<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\RunFeeReminders;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RunFeeReminders::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run daily at 9:00 AM as per requirements
        $schedule->command('fees:run-reminders')->dailyAt('09:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
