<?php

namespace App\Console\Commands\BookDescription;

use App\Jobs\InitBookDescriptionWordVectorsJob;
use Illuminate\Console\Command;

class InitBookDescriptionWordVectors extends Command
{
    protected $signature = 'bookDescription:initWordVectors {--debug}';

    protected $description = 'Составление векторов слов из словника по описанию книг';

    public function handle()
    {
        dispatch(new InitBookDescriptionWordVectorsJob($this->option('debug')));
    }
}
