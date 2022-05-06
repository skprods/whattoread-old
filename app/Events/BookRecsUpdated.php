<?php

namespace App\Events;

use App\Models\Book;

class BookRecsUpdated extends Event
{
    public Book $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }
}
