<?php

namespace App\Console\Commands;

use App\Jobs\ParseBukvoedJob;
use Illuminate\Console\Command;

class ParseBukvoedCommand extends Command
{
    protected $signature = 'parse:bukvoed {--debug}';

    protected $description = 'Парсер книг из интернет-магазина Буквоед';

    public function handle()
    {
        dispatch(new ParseBukvoedJob($this->option('debug')));
    }
}
