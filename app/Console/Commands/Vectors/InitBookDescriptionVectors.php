<?php

namespace App\Console\Commands\Vectors;

use App\Jobs\InitWordDescriptionVectorsJob;
use Illuminate\Console\Command;

class InitBookDescriptionVectors extends Command
{
    protected $signature = 'initVectors:bookDescription {--debug}';

    protected $description = 'Составление векторов слов из словника по описанию книг';

    public function handle()
    {
        dispatch(new InitWordDescriptionVectorsJob($this->option('debug')));
    }
}