<?php

namespace App\Console\Commands;

use App\Jobs\InitBookMatchesJob;
use Illuminate\Console\Command;

class InitBookMatchesCommand extends Command
{
    protected $signature = 'init:bookMatches {--start=} {--end=} {--debug}';

    protected $description = 'Составление рейтинга совпадения между книгами';

    public function handle()
    {
        dispatch(new InitBookMatchesJob(
            $this->option('start'),
            $this->option('end'),
            $this->option('debug') ?? false
        ));
    }
}
