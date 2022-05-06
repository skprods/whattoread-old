<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\BookVector;

class BookVectorManager
{
    private ?BookVector $bookVector;

    public function __construct(BookVector $bookVector)
    {
        $this->bookVector = $bookVector;
    }

    public function createOrUpdateDescription(Book $book, array $vector): BookVector
    {
        $this->bookVector = BookVector::find($book->id);

        if ($this->bookVector) {
            return $this->update($vector, null);
        } else {
            return $this->create($book, $vector, null);
        }
    }

    public function create(Book $book, ?array $descriptionVector, ?array $contentVector): BookVector
    {
        $this->bookVector = app(BookVector::class);

        if ($descriptionVector) {
            $this->bookVector->fill(['description' => $descriptionVector]);
        }

        if ($contentVector) {
            $this->bookVector->fill(['content' => $contentVector]);
        }

        $this->bookVector->fill(['book_id' => $book->id]);
        $this->bookVector->save();

        return $this->bookVector;
    }

    public function update(?array $descriptionVector, ?array $contentVector): BookVector
    {
        if ($descriptionVector) {
            $this->bookVector->fill(['description' => $descriptionVector]);
        }

        if ($contentVector) {
            $this->bookVector->fill(['content' => $contentVector]);
        }

        $this->bookVector->save();

        return $this->bookVector;
    }
}