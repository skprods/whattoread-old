<?php

namespace App\Services;

use App\Managers\BookRecsManager;
use App\Models\Book;
use App\Models\BookDescriptionFrequency;
use App\Models\Genre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Facades\Console;

class BookRecsService
{
    private const EXACT_MULTIPLIER = 5;

    private bool $debug;
    private BookRecsManager $bookRecommendationManager;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->bookRecommendationManager = app(BookRecsManager::class);
    }

    public function createForBooks(array $bookIds)
    {
        $books = Book::getByBookIds($bookIds);

        foreach ($books as $book) {
            $this->createForBook($book);
        }
    }

    /**
     * Составление рекомендаций для книги
     *
     * Принцип работы:
     * 1. Получаем частотные словник для выбранной книги и берём из него массив $word_id;
     * 2. Получаем частотные словники книг, у которых есть слова из массива в 1 шаге;
     * 3. Для каждого словника из шага 2 получаем процент совпадения:
     * 3.1. Получаем сумму частот исходной и сравниваемой книги (total frequency);
     * 3.2. Получаем сумму частот совпадающих слов (scores);
     * 3.3. Вычисляем процент совпадения: (scores / total frequency) * 100 с округлением до 2 знаков.
     * 4. Очищаем предыдущие составленные совпадения;
     * 5. Сохраняем полученные совпадения.
     */
    public function createForBook(Book $book)
    {
        $this->log($book, "Начинается составление рекомендаций для книги {$book->author} - {$book->title}");

        /** Получаем частотный словник сравниваемой книги и ID всех слов из него */
        $comparingWordFrequencies = $book->descriptionFrequencies->pluck('frequency', 'word_id')->toArray();
        $comparingWordIds = array_keys($comparingWordFrequencies);

        if (count($comparingWordFrequencies) === 0) {
            $this->error($book, "[отмена] Частотный словник по описанию не найден");
            return;
        }

        $this->log($book, "Частотный словник книги по описанию получен");

        $bookIdsForBookGenres = $this->getBookIdsByBookGenre($book);
        $bookIdsForBookGenres = array_chunk($bookIdsForBookGenres, 10000);
        $bookIds = [];
        foreach ($bookIdsForBookGenres as $bookIdsForBookGenre) {
            $bookIds = BookDescriptionFrequency::getBookIdsForRecs($comparingWordIds, $bookIdsForBookGenre)
                ->toArray() + $bookIds;
        }
        $bookIds = collect($bookIds);
        $bookIds->forget($book->id);

        if ($bookIds->count()) {
            $this->log($book, "Найдено {$bookIds->count()} книг");
            $this->bookRecommendationManager->deleteForBook($book->id);
            $this->log($book, "Данные по совпадениям для книги очищены");
        } else {
            $this->error($book, "[отменено] Похожие книги не найдены");
            return;
        }

        /** Разбиваем коллекцию книг на группы и получаем словники для группы книг */
        $count = 0;
        $bookIds->chunk(config('variables.matches.chunkSize'))->each(
            function (Collection $matchingBookIds) use ($book, $comparingWordFrequencies, $comparingWordIds, &$count) {
                /**
                 * $bookFrequencies - массив массивов вида [book_id => [word_id => frequency]]
                 * - словники всех книг, в которых есть слова из $wordIds
                 */
                $bookFrequencies = BookDescriptionFrequency::getBookFrequenciesByBookIds($matchingBookIds->toArray());
                $this->log($book, "Частотные словники похожих книг получены (для {$matchingBookIds->count()} книг)");

                $this->createRecommendationsFromBookFrequencies(
                    $bookFrequencies,
                    $book,
                    $comparingWordFrequencies,
                    $comparingWordIds
                );

                $count += $matchingBookIds->count();
            }
        );

        $this->log($book, "Совпадения подобраны и сохранены: $count книг");
    }

    /** Получение ID книг, относящихся к жанрам переданной книги */
    private function getBookIdsByBookGenre(Book $book): array
    {
        $genres = $book->genres;

        $genreIds = [];
        $genres->each(function (Genre $genre) use (&$genreIds) {
            $newGenres = $this->getGenreIdsByGenre($genre);
            $genreIds = $genreIds + $newGenres;
        });

        return Book::getBookIdsByGenreIds($genreIds);
    }

    /** Получение ID родительских и дочерних жанров по переданному */
    private function getGenreIdsByGenre(Genre $genre): array
    {
        $genreIds = [];

        $genre->parents->each(function (Genre $genre) use (&$genreIds) {
            $childs = $genre->childs->pluck('id', 'id')->toArray();
            $genreIds = $genreIds + $childs;
        });

        $genre->childs->each(function (Genre $genre) use (&$genreIds) {
            $genre->parents->each(function (Genre $genre) use (&$genreIds) {
                $childs = $genre->childs->pluck('id', 'id')->toArray();
                $genreIds = $genreIds + $childs;
            });
        });

        $genreIds[$genre->id] = $genre->id;

        return $genreIds;
    }

    private function createRecommendationsFromBookFrequencies(
        Collection $bookFrequencies,
        Book $book,
        array $comparingWordFrequencies,
        array $comparingWordIds
    ) {
        /** Жанры для книг */
        $bookGenres = [];
        /** Сумма всех частот сравниваемой книги */
        $wordTotalFrequency = $this->getExactTotalFrequency($comparingWordFrequencies);
        /** Процент описания */
        $bookScores = [];

        /** @var \Illuminate\Database\Eloquent\Collection|Book[] $books */
        $books = Book::getByBookIds($bookFrequencies->keys()->toArray())->mapWithKeys(function (Book $book) {
            return [$book->id => $book];
        });

        foreach ($bookFrequencies->toArray() as $bookId => $matchingWordFrequencies) {
            /**
             * Сумма частот сравниваемой и совпадающей книги. Это число отличается от 2,
             * потому что в словнике используются не все слова из текста
             */
            $totalFrequencies = $this->getExactTotalFrequency($matchingWordFrequencies) + $wordTotalFrequency;

            /** Сумма частот совпадающей книги */
            $scores = 0;

            /**
             * Проходим по всем словам из сравниваемой книги и проверяем, есть ли такие
             * в совпадающей книге. Если есть, суммируем frequencies с множителем.
             */
            foreach ($comparingWordIds as $wordId) {
                if (isset($matchingWordFrequencies[$wordId])) {
                    $comparingFrequency = $comparingWordFrequencies[$wordId];
                    $matchingFrequency = $matchingWordFrequencies[$wordId];
                    $scores += ($comparingFrequency + $matchingFrequency) * self::EXACT_MULTIPLIER;
                }
            }

            $bookScores[$bookId]['description_score'] = $this->getScore($scores / $totalFrequencies);

            if ($books[$bookId]->author === $book->author) {
                $bookScores[$bookId]['author_score'] = (int) $this->getScore(1);
            } else {
                $bookScores[$bookId]['author_score'] = (int) $this->getScore(0);
            }

            /** Вычисление веса за совпадения по жанрам */
            $comparingGenres = $this->getBookGenres($book->id, $bookGenres);
            $matchingGenres = $this->getBookGenres($bookId, $bookGenres);
            $genresCount = $this->getMatchingGenresCount($comparingGenres, $matchingGenres);
            $bookScores[$bookId]['genres_score'] = $genresCount;
        }

        $this->log($book, "Данные по совпадениям с книгами дополнены: {$bookFrequencies->count()} книг");

        /** Для каждой книги прописываем сравниваемую (текущую) и совпадающую */
        foreach ($bookScores as $matchingBookId => $params) {
            $bookScores[$matchingBookId] = array_merge($params, [
                'comparing_book_id' => $book->id,
                'matching_book_id' => $matchingBookId,
            ]);
        }

        $this->bookRecommendationManager->bulkCreate($bookScores);
        $this->log($book, "Сохранены совпадения: {$bookFrequencies->count()} книг");
    }

    private function getExactTotalFrequency(array $frequencies): float
    {
        return array_sum($frequencies) * self::EXACT_MULTIPLIER;
    }

    private function getScore(float $frequenciesSum): float
    {
        return round($frequenciesSum * 100, 2);
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

    public function log(Book $book, string $message)
    {
        if ($this->debug) {
            Console::info("[bookRecs #{$book->id}] $message");
        }

        Log::info("[bookRecs #{$book->id}] $message");
    }

    public function error(Book $book, string $message)
    {
        if ($this->debug) {
            Console::error("[bookRecs #{$book->id}] $message");
        }

        Log::error("[bookRecs #{$book->id}] $message");
    }
}