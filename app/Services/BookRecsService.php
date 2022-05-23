<?php

namespace App\Services;

use App\Managers\BookRecsManager;
use App\Models\Book;
use App\Models\BookDescriptionFrequency;
use App\Models\BookDescriptionFrequencyShort;
use App\Models\Genre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Facades\Console;

class BookRecsService extends Service
{
    private const EXACT_MULTIPLIER = 5;

    protected string $serviceName = "BookRecsService";

    /** Менеджер для сохранения данных по рекомендациям */
    private BookRecsManager $bookRecsManager;

    /** Частотные словники по описанию для оригинальных книг */
    private Collection $originDescriptionFrequencies;

    /** Жанры оригинальных книг */
    private Collection $originalBookGenres;

    /**
     * Данные по совпадающим книгам:
     * - частотные словники;
     * - жанры;
     * - авторы;
     */
    private Collection $matchingDescriptionFrequencies;
    private Collection $matchingBookGenres;
    private Collection $matchingBookAuthors;

    public function __construct(bool $debug = false)
    {
        parent::__construct($debug);

        $this->bookRecsManager = app(BookRecsManager::class);
    }

    public function createForBooks(array $bookIds): void
    {
        $this->log("Получаем данные для искомых книг...");
        $this->loadOriginalData($bookIds);
        $this->log("Данные получены");

        $this->log("Получаем искомые книги...");
        $books = Book::getByBookIds($bookIds);
        $this->log("Книги получены");

        foreach ($books as $book) {
            $time = time();

            $this->createForBook($book);

            $executionTime = time() - $time;
            $this->log("Наполнение рекомендаций для книги {$book->id} выполнено за {$executionTime}с");
            $this->log("Использовано памяти: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");
            $this->log(
                "Пиковое использование: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n"
            );
        }
    }

    /**
     * Загружаем информацию для оригинальных (искомых) книг:
     * - частотные словники;
     * - жанры;
     */
    private function loadOriginalData(array $bookIds): void
    {
        /** Загружаем словники по описанию для оригинальных книг */
        $this->originDescriptionFrequencies = BookDescriptionFrequencyShort::findByBookIds($bookIds);
        /** Загружаем жанры для всех оригинальных книг */
        $this->originalBookGenres = $this->getBooksGenres($bookIds);
    }

    /**
     * Загружаем информацию для совпадающих книг:
     * - частотные словники;
     * - жанры;
     * - авторы;
     */
    private function loadMatchingData(array $bookIds, int $identifier = null): void
    {
        /** Загружаем словники по описанию для совпадающих книг */
        $this->matchingDescriptionFrequencies = BookDescriptionFrequencyShort::findByBookIds($bookIds);
        $this->log("Словники по описанию получены", $identifier);

        /** Загружаем жанры для всех совпадающих книг */
        $this->matchingBookGenres = $this->getBooksGenres($bookIds);
        $this->log("Жанры получены", $identifier);

        /** Загружаем авторов для всех совпадающих книг */
        $this->matchingBookAuthors = Book::getBookAuthors($bookIds);
        $this->log("Авторы получены", $identifier);
    }

    /**
     * Составление рекомендаций для книги
     *
     * Принцип работы:
     * 1. Получаем частотные словник для выбранной книги и берём из него массив $word_id;
     * 2. Подготавливаем данные для совпадений:
     * 2.1. Получаем ID книг тех же жанров, что и у искомой книги;
     * 2.2. Получаем книги с совпадающими словами в их словниках (ограничивая выборку ID из 2.1)
     * 3. Получаем частотные словники книг, у которых есть слова из массива в 1 шаге;
     * 4. Для каждого словника из шага 3 получаем процент совпадения:
     * 4.1. Получаем сумму частот исходной и сравниваемой книги (total frequency);
     * 4.2. Получаем сумму частот совпадающих слов (scores);
     * 4.3. Вычисляем процент совпадения: (scores / total frequency) * 100 с округлением до 2 знаков.
     * 5. Очищаем предыдущие составленные совпадения;
     * 6. Сохраняем полученные совпадения.
     */
    private function createForBook(Book $book): void
    {
        $this->log("Начинается составление рекомендаций для книги {$book->author} - {$book->title}", $book->id);

        /**
         * 1. Получаем частотный словник сравниваемой книги и ID всех слов из него
         */
        $comparingWordFrequencies = $this->originDescriptionFrequencies->get($book->id) ?? [];
        $comparingWordIds = array_keys($comparingWordFrequencies);

        if (count($comparingWordFrequencies) === 0) {
            $this->error("[отмена] Частотный словник по описанию не найден", $book->id);
            return;
        } else {
            $this->log("Частотный словник книги по описанию получен", $book->id);
        }

        /**
         * 2. Подготавливаем данные для совпадений
         */

        /** 2.1. Получаем ID книг тех же жанров, что и у искомой книги */
        $bookIdsForBookGenres = $this->getBookIdsByBookGenre($book);
        $bookIdsForBookGenres = array_chunk($bookIdsForBookGenres, 10000);

        /** 2.2. Получаем книги с совпадающими словами в их словниках (ограничивая выборку ID из 2.1) */
        $this->log("Подбираем книги с совпадающими словами...", $book->id);
        $bookIds = [];
        foreach ($bookIdsForBookGenres as $bookIdsForBookGenre) {
            $bookIds = BookDescriptionFrequency::getBookIdsForRecs($comparingWordIds, $bookIdsForBookGenre)
                ->toArray() + $bookIds;
        }
        $bookIds = collect($bookIds);
        $bookIds->forget($book->id);

        if ($bookIds->count()) {
            $this->log("Найдено {$bookIds->count()} книг", $book->id);
            $this->bookRecsManager->deleteForBook($book->id);
            $this->log("Данные по совпадениям для книги очищены", $book->id);
        } else {
            $this->error("[отменено] Похожие книги не найдены", $book->id);
            return;
        }

        /**
         * 3. Получение данных по совпадающим книгам
         */
        $this->log("Получение данных по совпадающим книгам...", $book->id);
        $this->loadMatchingData($bookIds->toArray(), $book->id);
        $this->log("Все данные получены", $book->id);

        /**
         * 4. Составляем рекомендации для книги
         */
        $savedCount = $this->saveRecsForBook($book);
        $this->log("Совпадения подобраны и сохранены: $savedCount книг", $book->id);

        /** Освобождаем занятую совпадениями память */
        unset($this->matchingDescriptionFrequencies);
        unset($this->matchingBookGenres);
        unset($this->matchingBookAuthors);
    }

    /**
     * Сохранение рекомендаций для книги
     *
     * Перед этим шагом уже собрана следующая информация:
     * 1. Частотный словник этой книги;
     * 2. Из частотного словника есть ID всех слов этой книги;
     * 3. Собраны словники всех совпадающих книг (в $this->matchingDescriptionFrequencies)
     * 4. Собраны жанры всех совпадающих книг (в $this->matchingBookGenres)
     * 5. Собраны авторы всех совпадающих книг (в $this->matchingBookAuthors)
     *
     * @param Book $book    - книга
     * @return int          - число сохранённых рекомендаций
     */
    private function saveRecsForBook(Book $book): int
    {
        /** Получаем сумму всех частот из словника сравниваемой книги */
        $originBookTotalFrequency = $this->getExactTotalFrequency(
            $this->originDescriptionFrequencies->get($book->id)
        );

        $bookScores = [];

        $this->log("Подготовка данных по совпадающим книгам:", $book->id);
        $this->bar->start($this->matchingDescriptionFrequencies->count());
        /**
         * Проходим по каждому словнику в совпадающих книгах
         * и добавляем в массив $bookScores значения совпадений
         * с книгой, к которому относится словник.
         */
        $this->matchingDescriptionFrequencies
            ->each(function (
                array $matchingWordFrequencies,
                int $bookId
            ) use (
                $book,
                $originBookTotalFrequency,
                &$bookScores
            ) {
                $comparingWordFrequencies = $this->originDescriptionFrequencies->get($book->id);
                $comparingWordIds = array_keys($comparingWordFrequencies);

                /**
                 * Сумма частот сравниваемой и совпадающей книги. Это число отличается от 2,
                 * потому что в словнике используются не все слова из текста.
                 *
                 * Эта сумма частот нужна для определения процента совпадений (значение в знаменателе)
                 */
                $totalFrequencies = $this->getExactTotalFrequency($matchingWordFrequencies) + $originBookTotalFrequency;

                /**
                 * Проходим по всем словам из сравниваемой книги и проверяем, есть ли такие
                 * в совпадающей книге. Если есть, суммируем frequencies с множителем и
                 * добавляем к итоговым очкам $scores.
                 */
                $scores = 0;
                foreach ($comparingWordIds as $wordId) {
                    if (isset($matchingWordFrequencies[$wordId])) {
                        $comparingFrequency = $comparingWordFrequencies[$wordId];
                        $matchingFrequency = $matchingWordFrequencies[$wordId];
                        $scores += ($comparingFrequency + $matchingFrequency) * self::EXACT_MULTIPLIER;
                    }
                }

                /** Вычисляем очки совпадения по описанию */
                $descriptionScore = $this->getScore($scores / $totalFrequencies);

                /** Вычисляем очки совпадения по автору */
                $authorScore = $this->matchingBookAuthors->get($bookId) === $book->author ? 1 : 0;

                /** Вычисление веса за совпадения по жанрам */
                $genresScore = $this->getMatchingGenresCount(
                    $this->originalBookGenres->get($book->id),
                    $this->matchingBookGenres->get($bookId) ?? []
                );

                $matchingData = [
                    'comparing_book_id' => $book->id,
                    'matching_book_id' => $bookId,
                    'description_score' => $descriptionScore,
                    'author_score' => $authorScore,
                    'genres_score' => $genresScore,
                ];

                $totalChecker = $this->bookRecsManager->checkTotalAllowed($matchingData);
                if ($totalChecker['allowed'] === true) {
                    $bookScores[$bookId] = $matchingData;
                }

                $this->bar->advance();
            });

        $this->bar->finish();
        $this->consoleNewLine();

        $this->log("Данные по совпадающим книгам подготовлены. Сохраняем в БД...", $book->id);
        $savedCount = $this->bookRecsManager->bulkCreate($bookScores);
        $this->log("Сохранены совпадения: $savedCount книг", $book->id);

        return $savedCount;
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

    private function getExactTotalFrequency(array $frequencies): float
    {
        return array_sum($frequencies) * self::EXACT_MULTIPLIER;
    }

    private function getScore(float $frequenciesSum): float
    {
        return round($frequenciesSum * 100, 2);
    }

    public function getBooksGenres(array $bookIds): Collection
    {
        $chunked = array_chunk($bookIds, 10000);

        $data = new Collection();

        foreach ($chunked as $books) {
            $data = $data->union(DB::table('book_genre')
                ->whereIn('book_id', $books)
                ->get()
                ->mapToGroups(function (object $bookGenre) {
                    return [$bookGenre->book_id => $bookGenre->genre_id];
                })
                ->toArray());
        }

        return $data;
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
