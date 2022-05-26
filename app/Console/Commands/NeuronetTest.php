<?php

namespace App\Console\Commands;

use App\Entities\Subgenres;
use App\Models\Book;
use App\Models\Genre;
use App\Neuronets\GenresSingleClassifier;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use SKprods\LaravelHelpers\Facades\Console;

class NeuronetTest extends Command
{
    protected $signature = 'neuronet:test';
    protected $description = 'Проверка тестовых данных для нейросети';

    private GenresSingleClassifier $genresClassifier;
    private Subgenres $subgenres;

    public function __construct(GenresSingleClassifier $genresClassifier, Subgenres $subgenres)
    {
        parent::__construct();

        $this->genresClassifier = $genresClassifier;
        $this->subgenres = $subgenres;
    }

    public function handle()
    {
        $this->genresClassifier = new GenresSingleClassifier();
        $this->subgenres = new Subgenres();

        $testData = Book::query()
            ->whereHas('genres', function (Builder $query) {
//                Художественная литература
//                $query->whereIn('id', [
//                    1, 2, 6, 18, 31, 38, 54, 75, 90, 92, 121, 249, 255, 258, 334, 358, 370, 372, 843, 852
//                ]);
//                Психология
//                $query->whereIn('id', [
//                    138, 199, 201, 309, 323, 552, 712, 736, 737, 738, 739, 740, 741, 743, 744, 745, 746,
//                    747, 748, 749, 750, 751, 752, 753, 754, 755, 756, 757, 758, 759, 760, 761, 762, 889
//                ]);
//                Эзотерика
//                $query->whereIn('id', [
//                    383, 742, 857, 858, 859, 860, 861, 862, 863, 864, 865, 866, 867, 868, 869, 870, 871, 872,
//                    873, 874, 875, 876, 877, 878, 879, 880, 881, 882, 883, 884, 885, 886, 887, 888, 889, 890
//                ]);
//                Деловая литература
//                $query->whereIn('id', [
//                    277, 278, 279, 280, 281, 282, 283, 284, 674, 850, 286, 287, 288, 289, 290, 292, 293, 294, 295,
//                    296, 297, 298, 299, 300, 305, 620, 300, 302, 303, 304, 305, 306, 307, 308, 309, 310, 728, 312,
//                    313, 314, 315, 316, 317, 728, 187, 319, 321, 322, 323, 324, 850
//                ]);
//                Красота. Здоровье. Спорт
                $query->whereIn('id', [
                    400, 401, 403, 404, 858, 407, 410, 411, 415, 416, 417, 419, 420, 421,
                    422, 423, 424, 513, 516, 519, 520, 636, 426, 427, 428, 429, 430
                ]);
            })
            ->whereHas('vector')
            ->orderByDesc('id')
            ->limit(10000)
            ->get()
            ->map(function (Book $book) {
                $genres = [];
                $book->genres->each(function (Genre $genre) use (&$genres) {
                    $genres[$genre->id] = $genre->id;
                    $genres += $this->subgenres->getTopGenres($genre->id);
                });

                return [
                    'vector' => $book->vector->description,
                    'genres' => $genres,
                ];
            });

        $correctCalc = 0;
        $hasCorrectCalc = 0;
        $totalCalc = 0;

        $testData->each(
            function (array $data) use (&$correctCalc, &$hasCorrectCalc, &$totalCalc) {
                $activationVector = $this->genresClassifier->run($data['vector']);

                /** Получаем топ-5 жанров из вектора активации */
                $genres = $this->genresClassifier->getGenresByActivationVector($activationVector);
                $topGenres = array_slice($genres, 0, 5, true);
                $topGenreIds = array_keys($topGenres);

                /** Проверяем, есть ли в жанрах книги жанры из топ-5 от нейросети */
                foreach ($data['genres'] as $genreId) {
                    if (in_array($genreId, $topGenreIds)) {
                        $hasCorrectCalc++;
                    }
                }

                /** Проверяем, есть ли в жанрах книги жанр из топ-1 от нейросети */
                if (in_array($topGenreIds[0], $data['genres'])) {
                    $correctCalc++;
                }

                $totalCalc++;

                Console::info("$correctCalc / $hasCorrectCalc / $totalCalc");
            }
        );

        $correctPercent = round($correctCalc / $totalCalc * 100, 2);
        $hasCorrectPercent = round($hasCorrectCalc / $totalCalc * 100, 2);
        Console::info("Корректных подсчётов: $correctPercent%");
        Console::info("Подсчётов с правильным ответом: $hasCorrectPercent%");
    }
}
