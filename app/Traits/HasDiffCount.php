<?php

namespace App\Traits;

trait HasDiffCount
{
    /** Разница в количестве записей по сравнению с прошлым месяцем (сколько записей в этом месяце) */
    public static function getDiffCount(): int
    {
        return self::query()
            ->selectRaw('count(*) as total')
            ->whereRaw("year(curdate()) = year(created_at)")
            ->whereRaw("month(curdate()) = month(created_at)")
            ->first()
            ->total;
    }
}
