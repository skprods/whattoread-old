<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\BookDictionary;
use Illuminate\Support\Collection;

class BookDictionaryManager
{
    private ?BookDictionary $bookDictionary;

    public function __construct(BookDictionary $bookDictionary = null)
    {
        $this->bookDictionary = $bookDictionary;
    }

    public function createOrUpdate(Collection $dictionary, Book $book): BookDictionary
    {
        $this->bookDictionary = BookDictionary::query()->where('book_id', $book->id)->first();

        if ($this->bookDictionary) {
            return $this->update($dictionary, $book);
        } else {
            return $this->create($dictionary, $book);
        }
    }

    public function create(Collection $dictionary, Book $book): BookDictionary
    {
        $this->bookDictionary = app(BookDictionary::class);
        $this->bookDictionary->fill([
            'words' => $dictionary->toArray(),
        ]);
        $this->bookDictionary->book()->associate($book);
        $this->bookDictionary->save();

        return $this->bookDictionary;
    }

    public function update(Collection $dictionary, Book $book): BookDictionary
    {
        $this->bookDictionary->fill([
            'words' => $dictionary->toArray(),
        ]);
        $this->bookDictionary->book()->associate($book);
        $this->bookDictionary->save();

        return $this->bookDictionary;
    }
}