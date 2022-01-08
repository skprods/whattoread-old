<?php

namespace App\Traits;

trait HasDatabaseBooksCounter
{
    /** Количество уникальных book_id */
    public static function getBookCount(): int
    {
        return self::query()
            ->selectRaw('count(distinct book_id) as total')
            ->first()
            ->total;
    }

    /** Сколько уникальных book_id добавлено в этом месяце */
    public static function getBookDiffCount(): int
    {
        return self::query()
            ->selectRaw('count(distinct book_id) as total')
            ->whereRaw("year(curdate()) = year(created_at)")
            ->whereRaw("month(curdate()) = month(created_at)")
            ->first()
            ->total;
    }
}
