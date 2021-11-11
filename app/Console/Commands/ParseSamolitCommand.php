<?php

namespace App\Console\Commands;

use App\Jobs\ParseSamolitJob;
use Illuminate\Console\Command;

class ParseSamolitCommand extends Command
{
    protected $signature = 'parse:samolit {--start=56} {--end=95000}';

    protected $description = 'Парсинг интернет-портала Самолит';

    public function handle()
    {
        dispatch(new ParseSamolitJob(
            $this->option('start'),
            $this->option('end')
        ));
    }
}
