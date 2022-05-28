<?php

namespace App\Console\Commands;

use App\Entities\Subgenres;
use App\Models\Book;
use App\Models\Genre;
use App\Neuronets\GenresNeuronet;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use SKprods\AdvancedLaravel\Facades\Console;
use Symfony\Component\Console\Helper\ProgressBar;

class GenresNeuronetTrain extends Command
{
    protected $signature = 'neuronet:trainGenres {--epoch=10} {--learning=0.0001} {--chunk=10000}';
    protected $description = 'Тренировка классификатора жанров';

    private GenresNeuronet $genresNeuronet;
    private Subgenres $subgenres;
    private ProgressBar $bar;

    private float $learningCoefficient;
    private int $chunk;

    public function __construct(GenresNeuronet $genresNeuronet, Subgenres $subgenres)
    {
        parent::__construct();

        $this->genresNeuronet = $genresNeuronet;
        $this->bar = Console::bar();
        $this->subgenres = $subgenres;
    }

    public function handle()
    {
        Console::info("Запускаем тренировку классификатора жанров.");

        $epochCount = (int) $this->option('epoch');

        $this->learningCoefficient = $this->option('learning');
        $this->chunk = (int) $this->option('chunk');

        for ($epoch = 1; $epoch <= $epochCount; $epoch++) {
            Console::info("::: Эпоха обучения $epoch :::");
            $this->train();
        }
    }

    private function train()
    {
        $chunkNumber = 0;
        $totalBooks = 0;

        Book::query()
            ->with(['genres', 'vector'])
            ->chunk($this->chunk, function (Collection $data) use (&$chunkNumber, &$totalBooks) {
                Console::info("Получение книг из БД, смещение: " . $chunkNumber * $this->chunk);
                Console::info("Подготовка данных...");
                $data = $this->filterData($data);
                $data = $this->mapData($data);

                Console::info("Данные подготовлены. Начало обучения.");
                $this->genresNeuronet->train($data, $this->learningCoefficient);

                $chunkNumber++;
                $totalBooks += $data->count();
                Console::info("Пройдено $totalBooks книг");
                Console::info("Использовано памяти: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n");
            });
    }

    private function filterData(Collection $data): Collection
    {
        Console::info("Фильтруем данные и оставляем только книги с вектором и жанрами...");
        $this->bar->start($data->count());
        $data = $data->filter(function (Book $book) {
            $this->bar->advance();
            return $book->vector && $book->genres->isNotEmpty();
        });
        $this->bar->finish();
        Console::info(' Выполнено.');

        return $data;
    }

    private function mapData(Collection $data): Collection
    {
        Console::info("Преобразуем данные в нужный формат...");

        $this->bar->start($data->count());
        $data = $data->map(function (Book $book) {
            $genres = [];
            $book->genres->each(function (Genre $genre) use (&$genres) {
                $genres[$genre->id] = $genre->id;
                $genres += $this->subgenres->getGenres($genre->id);
            });

            $this->bar->advance();
            return [
                'vector' => $book->vector->description,
                'genres' => $genres,
            ];
        });
        $this->bar->finish();
        Console::info(' Выполнено.');

        return $data;
    }
}
