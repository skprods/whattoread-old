<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\BookAssociation;

class BookAssociationManager
{
    private ?BookAssociation $bookAssociation;

    public function __construct(BookAssociation $bookAssociation)
    {
        $this->bookAssociation = $bookAssociation;
    }

    public function bulkCreate(array $associations, Book $book)
    {
        foreach ($associations as $association) {
            $this->create(['association' => $association], $book);
        }
    }

    public function createOrIncrement(string $association, Book $book): BookAssociation
    {
        $this->bookAssociation = BookAssociation::findAssociationForBook($association, $book->id);

        if ($this->bookAssociation) {
            return $this->incrementTotal();
        } else {
            return $this->create(['association' => $association], $book);
        }
    }

    public function create(array $params, Book $book)
    {
        $params['association'] = $this->prepareAssociation($params['association']);

        $this->bookAssociation = app(BookAssociation::class);
        $this->bookAssociation->fill($params);
        $this->bookAssociation->book()->associate($book);
        $this->bookAssociation->save();

        return $this->bookAssociation;
    }

    private function prepareAssociation(string $association): string
    {
        return trim(mb_strtolower($association));
    }

    public function incrementTotal(): BookAssociation
    {
        $this->bookAssociation->total += 1;
        $this->bookAssociation->save();

        return $this->bookAssociation;
    }
}
