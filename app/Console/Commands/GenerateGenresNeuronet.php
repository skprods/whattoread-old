<?php

namespace App\Console\Commands;

use App\Models\Genre;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class GenerateGenresNeuronet extends Command
{
    protected $signature = 'neuronet:generateGenres';

    protected $description = 'Генерация нейросети для жанров';

    public function handle()
    {
        $neurons = [];
        Genre::query()->with(['parents'])->chunk(100, function (Collection $genres) use (&$neurons) {
            $genres->each(function (Genre $genre) use (&$neurons) {
                /** Нужны только жанры первого уровня - без родительских */
                if ($genre->parents->isEmpty()) {
                    $neurons[] = [
                        'weights' => [],
                        'offset' => null,
                        'data' => [
                            'genreId' => $genre->id,
                            'genreName' => $genre->name,
                        ],
                    ];
                }
            });
        });

        $data = [
            'name' => 'Нейросеть по подбору жанров под книгу',
            'layers' => [
                [
                    'position' => 1,
                    'neurons' => $neurons,
                ]
            ],
        ];

        $neuronet = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents(resource_path('neuronets/genres.json'), $neuronet);
    }
}
