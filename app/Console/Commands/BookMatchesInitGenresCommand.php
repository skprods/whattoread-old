<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookMatchesInitGenresCommand extends Command
{
    protected $signature = 'bookMatches:initGenres {--chunk=100000} {--save=1000}';

    protected $description = 'Command description';

    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $save = (int) $this->option('save');
        $bookGenres = [];
        $total = 0;
        Log::info("[bookMatchesGenres]: Начинается выставление веса за жанры");

        DB::table('book_matches')
            ->orderBy('id')
            ->chunk($chunk, function (Collection $data) use ($chunk, $save, &$bookGenres, &$total) {
                Log::info("[bookMatchesGenres]: Получена пачка в $chunk записей");
                $data = $data->map(function ($bookMatch) use (&$bookGenres) {
                    $comparingGenres = $this->getBookGenres($bookMatch->comparing_book_id, $bookGenres);
                    $matchingGenres = $this->getBookGenres($bookMatch->matching_book_id, $bookGenres);

                    $bookMatch->genres_score = $this->getMatchingGenresCount($comparingGenres, $matchingGenres);
                    $bookMatch->total_score += $bookMatch->genres_score * 10;

                    return (array) $bookMatch;
                });

                Log::info("[bookMatchesGenres]: Данные преобразованы, начинается обновление");

                $data->chunk($save)->each(function (Collection $chunked) use ($save) {
                    DB::table('book_matches')->upsert($chunked->toArray(), 'id');
                    Log::info("[bookMatchesGenres]: Обновлено $save записей");
                });

                $total += $chunk;
                Log::info("[bookMatchesGenres]: Данные обновлены. Всего: $total");
            });

        Log::info("[bookMatchesGenres]: Добавление веса за жанры в сопадения завершено");
    }

    private function getBookGenres(int $bookId, array &$bookGenres): array
    {
        if (!isset($bookGenres[$bookId])) {
            $genres = DB::table('book_genre')
                ->where('book_id', $bookId)
                ->orderBy('book_id')
                ->select('genre_id')
                ->get()
                ->pluck('genre_id');
            $bookGenres[$bookId] = $genres->all();
        }

        return $bookGenres[$bookId];
    }

    private function getMatchingGenresCount(array $comparingGenres, array $matchingGenres): int
    {
        $count = 0;

        if (!empty($comparingGenres) && !empty($matchingGenres)) {
            foreach ($comparingGenres as $comparingGenre) {
                $count += in_array($comparingGenre, $matchingGenres) ? 1 : 0;
            }

            if ($count > 4) {
                $count = 4;
            }
        }

        return $count;
    }
}
