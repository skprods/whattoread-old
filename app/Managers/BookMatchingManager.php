<?php

namespace App\Managers;

use App\Models\BookMatching;

class BookMatchingManager
{
    private ?BookMatching $bookMatching;

    public function __construct(BookMatching $bookMatching)
    {
        $this->bookMatching = $bookMatching;
    }

    /**
     * @param array $matching - массив вида [0 => $params1, 1 => $params2]
     */
    public function bulkCreate(array $matching)
    {
        foreach ($matching as $params) {
            $this->createOrUpdate($params);
        }
    }

    public function createOrUpdate(array $params)
    {
        if (!isset($params['comparing_book_id']) || !isset($params['matching_book_id'])) {
            throw new \Exception('Не переданы параметры comparing_book_id и/или matching_book_id');
        }

        $this->bookMatching = BookMatching::firstByBookIds($params['comparing_book_id'], $params['matching_book_id']);

        if ($this->bookMatching) {
            return $this->update($params);
        } else {
            return $this->createIfAllowed($params);
        }
    }

    public function createIfAllowed(array $params): ?BookMatching
    {
        // TODO: if total_score === 0
        if (!$params['description_score'] && !$params['author_score']) {
            return null;
        }

        return $this->create($params);
    }

    public function create(array $params): BookMatching
    {
        $this->bookMatching = app(BookMatching::class);
        $this->bookMatching->fill($params);
        $this->bookMatching->comparingBook()->associate($params['comparing_book_id']);
        $this->bookMatching->matchingBook()->associate($params['matching_book_id']);
        $this->bookMatching->save();

        return $this->bookMatching;
    }

    public function update(array $params): BookMatching
    {
        $this->bookMatching->fill($params);
        $this->bookMatching->save();

        return $this->bookMatching;
    }

    public function deleteForBook(int $bookId)
    {
        BookMatching::deleteByBookId($bookId);
    }
}