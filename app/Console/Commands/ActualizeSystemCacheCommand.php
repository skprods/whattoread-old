<?php

namespace App\Console\Commands;

use App\Jobs\ActualizeSystemCacheJob;
use Illuminate\Console\Command;

class ActualizeSystemCacheCommand extends Command
{
    protected $signature = 'actualize:systemCache';

    protected $description = 'Обновляет кэш с информацией о системе';

    public function handle()
    {
        dispatch(new ActualizeSystemCacheJob());
    }
}
