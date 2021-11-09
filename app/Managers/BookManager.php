<?php

namespace App\Managers;

use App\Models\Book;

class BookManager
{
    private ?Book $book;

    public function __construct(Book $book = null)
    {
        $this->book = $book;
    }

    public function create(array $params): Book
    {
        $this->book = app(Book::class);
        $this->book->fill($params);
        $this->book->save();

        return $this->book;
    }

    public function update(array $params): Book
    {
        $this->book->fill($params);
        $this->book->save();

        return $this->book;
    }
}
