<?php

namespace App\Managers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardManager
{
    public function getMessagesCountByMonth(string $month = null): array
    {
        if (!$month) {
            $date = now();
        } else {
            $date = Carbon::createFromFormat("Y-m-d", $month);
        }

        $month = $date->format('m');
        $year = $date->format('Y');

        $nextDate = $date->addMonth();
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

        $messages = [];
        foreach ($res as $item) {
            $messages['days'][] = $item->daynum;
            $messages['total'][] = $item->total;
        }

        return $messages;
    }
}
