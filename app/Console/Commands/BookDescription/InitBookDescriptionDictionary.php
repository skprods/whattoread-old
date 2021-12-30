<?php

namespace App\Console\Commands\BookDescription;

use App\Jobs\InitBookDescriptionDictionaryJob;
use Illuminate\Console\Command;

class InitBookDescriptionDictionary extends Command
{
    protected $signature = 'bookDescription:initDictionary {--start=} {--end=} {--debug}';

    protected $description = 'Составление частотного словника по описанию книг';

    public function handle()
    {
        dispatch(new InitBookDescriptionDictionaryJob(
            $this->option('start'),
            $this->option('end'),
            $this->option('debug') ?? false
        ));
    }
}
