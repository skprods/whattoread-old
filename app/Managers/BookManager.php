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

    public function createOrUpdate(array $params): Book
    {
        $this->book = Book::findByShopBookIdAndShopName($params['shop_book_id'], $params['shop_name']);

        if ($this->book) {
            return $this->update($params);
        } else {
            return $this->create($params);
        }
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
