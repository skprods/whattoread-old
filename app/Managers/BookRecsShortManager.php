<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\BookRecsShort;

class BookRecsShortManager
{
    public function create(Book|int $book, array $data): BookRecsShort
    {
        /** @var BookRecsShort $recs */
        $recs = app(BookRecsShort::class);
        $recs->data = $data;
        $recs->book()->associate($book);
        $recs->save();

        return $recs;
    }

    public function delete(int $bookId)
    {
        BookRecsShort::query()->where('book_id', $bookId)->delete();
    }
}