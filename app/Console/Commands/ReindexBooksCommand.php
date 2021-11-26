<?php

namespace App\Console\Commands;

use App\Jobs\ReindexBooksJob;
use Illuminate\Console\Command;

class ReindexBooksCommand extends Command
{
    protected $signature = 'reindex:books {--debug}';

    protected $description = 'Полная переиндексация книг';

    public function handle()
    {
        dispatch(new ReindexBooksJob($this->option('debug')));
    }
}
