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

    public function create(array $params, Book $book)
    {
        $this->bookAssociation = app(BookAssociation::class);
        $this->bookAssociation->fill($params);
        $this->bookAssociation->book()->associate($book);
        $this->bookAssociation->save();

        return $this->bookAssociation;
    }
}
