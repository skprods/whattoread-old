<?php

namespace App\Console\Commands;

use App\Jobs\ActualizeSystemCacheJob;
use Illuminate\Console\Command;

class ActualizeSystemCacheCommand extends Command
{
    protected $signature = 'actualize:systemCache {--debug}';

    protected $description = 'Актуализация кэша с информацией о системе';

    public function handle()
    {
        dispatch(new ActualizeSystemCacheJob($this->option('debug')));
    }
}
