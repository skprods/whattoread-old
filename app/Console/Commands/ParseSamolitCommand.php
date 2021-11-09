<?php

namespace App\Console\Commands;

use App\Jobs\ParseSamolitJob;
use Illuminate\Console\Command;

class ParseSamolitCommand extends Command
{
    protected $signature = 'parse:samolit';

    protected $description = 'Парсинг интернет-портала Самолит';

    public function handle()
    {
        dispatch(new ParseSamolitJob());
    }
}
