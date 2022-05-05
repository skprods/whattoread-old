<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateWordVectors extends Command
{
    protected $signature = 'words:generateVectors {--debug}';

    protected $description = 'Генерирование векторов для слов, у которых их ещё нет';

    public function handle()
    {
        dispatch(new \App\Jobs\GenerateWordVectors($this->option('debug')));
    }
}
