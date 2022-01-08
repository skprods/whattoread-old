<?php

namespace App\Console\Commands;

use App\Jobs\InitBookDescriptionDictionaryJob;
use Illuminate\Console\Command;

class InitBookDescriptionDictionaryCommand extends Command
{
    protected $signature = 'init:descriptionDictionary {--start=} {--end=} {--debug}';

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