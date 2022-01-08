<?php

namespace App\Services;

use App\Managers\BookMatchingManager;
use App\Models\Book;
use App\Models\BookDescriptionFrequency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class BookMatchingService
{
    private const EXACT_MULTIPLIER = 5;

    private bool $debug;
    private BookMatchingManager $bookMatchingManager;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->bookMatchingManager = app(BookMatchingManager::class);
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

        /**
         * $bookFrequencies - массив массивов вида [book_id => [word_id => frequency]]
         * - словники всех книг, в которых есть слова из $wordIds
         */
        $bookFrequencies = BookDescriptionFrequency::getBookFrequenciesByWordIds($comparingWordIds);
        unset($bookFrequencies[$book->id]);

        if (count($bookFrequencies) === 0) {
            $this->error($book, "[отменено] Частотные словники похожих книг не найдены");
            return;
        }

        $this->log($book, "Частотные словники похожих книг получены");

        /** Сумма всех частот сравниваемой книги */
        $wordTotalFrequency = $this->getExactTotalFrequency($comparingWordFrequencies);
        /** Процент описания */
        $bookScores = [];

        /** @var Collection|Book[] $books */
        $books = Book::getByBookIds(array_keys($bookFrequencies))->mapWithKeys(function (Book $book) {
            return [$book->id => $book];
        });

        foreach ($bookFrequencies as $bookId => $matchingWordFrequencies) {
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
        }

        $this->log($book, "Данные по совпадениям с книгами наполнены");

        foreach ($bookScores as $matchingBookId => $params) {
            $bookScores[$matchingBookId] = array_merge($params, [
                'comparing_book_id' => $book->id,
                'matching_book_id' => $matchingBookId,
            ]);
        }

        $this->bookMatchingManager->deleteForBook($book->id);
        $this->log($book, "Данные по совпадениям для книги очищены");

        $this->bookMatchingManager->bulkCreate($bookScores);
        $this->log($book, "Данные по совпадениям с книгами сохранены");
    }

    private function getExactTotalFrequency(array $frequencies): float
    {
        return array_sum($frequencies) * self::EXACT_MULTIPLIER;
    }

    private function getScore(float $frequenciesSum): float
    {
        return round($frequenciesSum * 100, 2);
    }

    public function log(Book $book, string $message)
    {
        if ($this->debug) {
            Console::info("[bookMatching #{$book->id}] $message");
        }

        Log::info("[bookMatching #{$book->id}] $message");
    }

    public function error(Book $book, string $message)
    {
        if ($this->debug) {
            Console::error("[bookMatching #{$book->id}] $message");
        }

        Log::error("[bookMatching #{$book->id}] $message");
    }
}