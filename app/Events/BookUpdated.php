<?php

namespace App\Events;

use App\Models\Book;

class BookUpdated extends Event
{
    public Book $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }
}
