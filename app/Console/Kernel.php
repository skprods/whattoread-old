<?php

namespace App\Console;

use App\Console\Commands\ActualizeSystemCacheCommand;
use App\Console\Commands\HealthCheckCommand;
use App\Console\Commands\ReindexBooksCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ActualizeSystemCacheCommand::class,
        HealthCheckCommand::class,
        ReindexBooksCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        /** Актуализация кэша с информацией о системе */
        $schedule->command(ActualizeSystemCacheCommand::class)->everyMinute();
        /** Проверка работоспособности всех частей приложения */
        $schedule->command(HealthCheckCommand::class)->everyFiveMinutes();
        /** Полная переиндексация книг */
        $schedule->command(ReindexBooksCommand::class)->dailyAt("01:00");
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
