<?php

namespace App\Managers;

use App\Models\BookMatching;
use Illuminate\Support\Facades\DB;

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
        $allowed = [];

        foreach ($matching as $params) {
            $item = $this->prepareParams($params);

            if ($item) {
                $allowed[] = $item;
            }

            if (count($allowed) === 1000) {
                DB::table('book_matches')->insert($allowed);
                $allowed = [];
            }
        }

        if (count($allowed)) {
            DB::table('book_matches')->insert($allowed);
        }
    }

    private function prepareParams(array $params): ?array
    {
        if (!isset($params['comparing_book_id']) || !isset($params['matching_book_id'])) {
            return null;
        }

        $total = $params['author_score'] + $params['description_score'];
        if ($total === 0) {
            return null;
        }

        return [
            'comparing_book_id' => $params['comparing_book_id'],
            'matching_book_id' => $params['matching_book_id'],
            'author_score' => $params['author_score'] > 0 ? 1 : 0,
            'description_score' => $params['description_score'],
            'total_score' => $total,
        ];
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