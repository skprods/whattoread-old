<?php

namespace App\Managers;

use App\Models\BookRecs;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

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
    public function bulkCreate(array $matching): int
    {
        $allowedCount = 0;
        $allowed = [];
        $table = $this->bookRecs->getTable();

        foreach ($matching as $params) {
            $item = $this->prepareParams($params);

            if ($item) {
                $allowedCount++;
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

        return $allowedCount;
    }

    /** Подготовка параметров для вставки в БД */
    private function prepareParams(array $params): ?array
    {
        if (!isset($params['comparing_book_id']) || !isset($params['matching_book_id'])) {
            return null;
        }

        /**
         * Для оптимизации занимаемого базой данных места в
         * конфигурации проставлено минимальное суммарное значение
         * совпадений. Если по $total не подходит, возвращаем null
         */
        $totalChecker = $this->checkTotalAllowed($params);
        if ($totalChecker['allowed'] === false) {
            return null;
        }

        return [
            'comparing_book_id' => $params['comparing_book_id'],
            'matching_book_id' => $params['matching_book_id'],
            'author_score' => BookRecs::getAuthorDbValue($params['author_score']),
            'genres_score' => BookRecs::getGenresDbValue($params['genres_score']),
            'description_score' => $params['description_score'],
            'total_score' => $totalChecker['total'],
        ];
    }

    #[ArrayShape(['total' => "float", 'allowed' => "bool"])]
    public function checkTotalAllowed(array $params): array
    {
        /**
         * Считаем общую сумму значений
         *
         * Она вычитается как сумма очков за совпадение автора,
         * жанров и словнику по описанию. Максимальное количество:
         * - 40 за автора
         * - 40 за жанры
         * - 100 за словник
         * Итого 180.
         */
        $authorScore = BookRecs::getAuthorScore($params['author_score']);
        $genresScore = BookRecs::getGenresScore($params['genres_score']);
        $total = $authorScore + $genresScore + $params['description_score'];

        /**
         * Для оптимизации занимаемого базой данных места в
         * конфигурации проставлено минимальное суммарное значение
         * совпадений. Если получившийся $total меньше, ставим
         * ключ allowed = false
         */
        $minTotal = config('variables.matches.minTotalScore');
        return [
            'total' => $total,
            'allowed' => $total >= $minTotal,
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