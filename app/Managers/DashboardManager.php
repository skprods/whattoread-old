<?php

namespace App\Managers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardManager
{
    public function getBotActivity(string $datetime = null): array
    {
        if (!$datetime) {
            $date = now();
        } else {
            $date = Carbon::createFromFormat("Y-m-d", $datetime);
        }

        $month = $date->format('m');
        $year = $date->format('Y');

        $nextDate = clone $date;
        $nextDate = $nextDate->addMonth();
        $nextMonth = $nextDate->format('m');
        $nextYear = $nextDate->format('Y');

        $start = "$year-$month-01 00:00:00";
        $end = "$nextYear-$nextMonth-01 00:00:00";

        DB::statement(DB::raw("call make_intervals('$start','$end',1,'DAY')"));

        $res = DB::select(DB::raw(
            "select ifnull(tm.total,0) as total, day(ti.interval_start) as daynum from (
                select
                    count(*) as total,
                    day(created_at) as daynum
                from telegram_messages tm
                where tm.created_at >= date('$start')
                and tm.created_at < date('$end')
                group by daynum
            ) as tm
            right join time_intervals ti on day(ti.interval_start) = tm.daynum"
        ));

        $now = now();
        $maxDay = null;
        if ($now->year === $date->year && $now->month === $date->month) {
            $maxDay = $now->day;
        }

        $messages = [];
        foreach ($res as $key => $item) {
            if ($maxDay && $key === $maxDay - 1) {
                break;
            }

            $messages['days'][] = $item->daynum;
            $messages['total'][] = $item->total;
        }

        return $messages;
    }

    public function getBotActiveUsers(int $year = null): array
    {
        $year = $year ?? now()->format('Y');
        $nextYear = $year + 1;

        $start = "$year-01-01 00:00:00";
        $end = "$nextYear-01-01 00:00:00";

        DB::statement(DB::raw("call make_intervals('$start','$end',1,'MONTH')"));

        $res = DB::select(DB::raw(
            "select ifnull(tm.total,0) as total, month(ti.interval_start) as mon from (
                select
                    count(distinct(tm.telegram_user_id)) as total,
                    month(created_at) as mon
                from telegram_messages tm
                where tm.created_at >= date('$start')
                and tm.created_at < date('$end')
                group by mon
            ) as tm
            right join time_intervals ti on month(ti.interval_start) = tm.mon"
        ));

        $users = [];
        foreach ($res as $item) {
            $users['month'][] = $item->mon;
            $users['total'][] = $item->total;
        }

        return $users;
    }
}
