<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasDatabaseCounter
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

    public static function countByMonth(int $month = null, int $year = null): int
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $date = Carbon::createFromFormat('Y-m-d', "$year-$month-01");

        $currentMonth = $date->format('Y-m-d H:i:s');
        $nextDate = clone $date;
        $nextDate->addMonth();
        $nextMonth = $nextDate->format('Y-m-d H:i:s');

        return self::query()
            ->where('created_at', '>=', $currentMonth)
            ->where('created_at', '<', $nextMonth)
            ->count();
    }

    public static function diffByMonth(
        int $month = null,
        int $year = null,
        int $diffMonth = null,
        int $diffYear = null
    ): int {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $currentCount = self::countByMonth($month, $year);

        $currentTimeString = "$year-$month-01 00:00:00";
        $currentDate = Carbon::createFromFormat("Y-m-d H:i:s", $currentTimeString);
        $currentDate->subMonth();

        $diffMonth = $diffMonth ?? $currentDate->month;
        $diffYear = $diffYear ?? $currentDate->year;
        $diffCount = self::countByMonth($diffMonth, $diffYear);

        return $currentCount - $diffCount;
    }

    public static function countByYear(int $year = null): int
    {
        $year = $year ?? now()->year;
        $currentYear = "$year-01-01 00:00:00";
        $nextYear = ++$year . "-01-01 00:00:00";

        return self::query()
            ->where('created_at', '>=', $currentYear)
            ->where('created_at', '<', $nextYear)
            ->count();
    }

    public static function diffByYear(int $year = null, int $diffYear = null): int
    {
        $year = $year ?? now()->year;
        $diffYear = $diffYear ?? $year - 1;

        $currentCount = self::countByYear($year);
        $diffCount = self::countByYear($diffYear);

        return $currentCount - $diffCount;
    }
}
