<?php

namespace App\Services;

use App\Managers\BookRecsShortManager;
use App\Models\BookRecs;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class BookRecsShortService
{
    private BookRecsShortManager $manager;
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->manager = app(BookRecsShortManager::class);
    }

    public function createForBooks(array $bookIds)
    {
        foreach ($bookIds as $bookId) {
            $this->createForBook($bookId);
        }
    }

    public function createForBook(int $bookId)
    {
        $this->log($bookId, "Подготовка быстрых рекомендаций для #$bookId");
        $this->manager->delete($bookId);
        $this->log($bookId, "Предыдущие быстрые рекомендации удалены");

        $recs = BookRecs::getByBookId($bookId);
        $this->log($bookId, "Рекомендации получены");

        $data = [];
        $recs->each(function (BookRecs $rec) use (&$data, $bookId) {
            $recsBookId = $rec->comparing_book_id === $bookId ? $rec->matching_book_id : $rec->comparing_book_id;

            $data[$recsBookId] = [
                'book_id' => $recsBookId,
                'author_score' => $rec->author_score,
                'total_score' => $rec->total_score,
            ];
        });

        $this->manager->create($bookId, $data);
        $this->log($bookId, "Рекомендации загружены");
    }

    public function log(int $bookId, string $message)
    {
        if ($this->debug) {
            Console::info("[bookRecsShort #{$bookId}] $message");
        }

        Log::info("[bookRecsShort #{$bookId}] $message");
    }

    public function error(int $bookId, string $message)
    {
        if ($this->debug) {
            Console::error("[bookRecsShort #{$bookId}] $message");
        }

        Log::error("[bookRecsShort #{$bookId}] $message");
    }
}