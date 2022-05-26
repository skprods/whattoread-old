<?php

namespace App\Console\Commands;

use App\Neuronets\GenresNeuronet;
use Illuminate\Console\Command;

class GenerateGenresNeuronet extends Command
{
    protected $signature = 'neuronet:generateGenres';

    protected $description = 'Генерация нейросети для жанров';

    public function handle()
    {
        GenresNeuronet::generate();
    }
}
