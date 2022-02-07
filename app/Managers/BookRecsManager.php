<?php

namespace App\Managers;

use App\Models\BookRecs;
use Illuminate\Support\Facades\DB;

class BookRecsManager
{
    private ?BookRecs $bookRecs;

    public function __construct(BookRecs $bookRecs)
    {
        $this->bookRecs = $bookRecs;
    }

    /**
     * @param array $matching - массив вида [0 => $params1, 1 => $params2]
     */
    public function bulkCreate(array $matching)
    {
        $allowed = [];
        $table = $this->bookRecs->getTable();

        foreach ($matching as $params) {
            $item = $this->prepareParams($params);

            if ($item) {
                $allowed[] = $item;
            }

            if (count($allowed) === 1000) {
                DB::table($table)->upsert(
                    $allowed,
                    ['comparing_book_id', 'matching_book_id'],
                    ['author_score', 'description_score', 'total_score']
                );
                $allowed = [];
            }
        }

        if (count($allowed)) {
            DB::table($table)->upsert(
                $allowed,
                ['comparing_book_id', 'matching_book_id'],
                ['author_score', 'description_score', 'total_score']
            );
        }
    }

    private function prepareParams(array $params): ?array
    {
        if (!isset($params['comparing_book_id']) || !isset($params['matching_book_id'])) {
            return null;
        }

        /** total - сумма очков за автора и описание, максимум 200. для оптимизации отсекаем всё, что меньше $minTotal из 200 */
        $total = $params['author_score'] + $params['description_score'];
        $minTotal = config('variables.matches.minTotalScore');
        if ($total < $minTotal) {
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

        $this->bookRecs = BookRecs::firstByBookIds($params['comparing_book_id'], $params['matching_book_id']);

        if ($this->bookRecs) {
            return $this->update($params);
        } else {
            return $this->createIfAllowed($params);
        }
    }

    public function createIfAllowed(array $params): ?BookRecs
    {
        // TODO: if total_score === 0
        // TODO: сделать статичную функцию у модели calcTotalScore($params): int
        if (!$params['description_score'] && !$params['author_score'] && !$params['genres_score']) {
            return null;
        }

        return $this->create($params);
    }

    public function create(array $params): BookRecs
    {
        $this->bookRecs = app(BookRecs::class);
        $this->bookRecs->fill($params);
        $this->bookRecs->comparingBook()->associate($params['comparing_book_id']);
        $this->bookRecs->matchingBook()->associate($params['matching_book_id']);
        $this->bookRecs->save();

        return $this->bookRecs;
    }

    public function update(array $params): BookRecs
    {
        $this->bookRecs->fill($params);
        $this->bookRecs->save();

        return $this->bookRecs;
    }

    public function deleteForBook(int $bookId)
    {
        BookRecs::deleteByBookId($bookId);
    }
}