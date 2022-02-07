<?php

namespace App\Console\Commands;

use App\Jobs\InitBookRecsJob;
use Illuminate\Console\Command;

class InitBookRecsCommand extends Command
{
    protected $signature = 'recs:initBooks {--start=} {--end=} {--debug}';

    protected $description = 'Составление рекомендаций для книг';

    public function handle()
    {
        dispatch(new InitBookRecsJob(
            $this->option('start'),
            $this->option('end'),
            $this->option('debug') ?? false
        ));
    }
}
