<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeleteNotDistinctWordsCommand extends Command
{
    protected $signature = 'delete:not-distinct';

    protected $description = 'Удаление повторяющихся словоформ';

    public function handle()
    {
        DB::statement(DB::raw("SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));"));

        $ids = [];

        $builder = DB::table('words')
            ->select('id')
            ->groupBy(['word', 'type'])
            ->orderBy('id');

        $builder
            ->chunk(100000, function (Collection $data) use (&$ids) {
                $ids[] = $data->pluck('id')->implode(',');
            });

        $ids = implode(',', $ids);
        DB::delete(DB::raw("DELETE FROM words WHERE id NOT IN ($ids)"));
    }
}
