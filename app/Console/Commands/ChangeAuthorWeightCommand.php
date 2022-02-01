<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChangeAuthorWeightCommand extends Command
{
    protected $signature = 'authorWeight:change {--chunk=100000} {--save=1000}';

    protected $description = 'Изменение веса автора в совпадении со 100 до 40';

    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $save = (int) $this->option('save');
        Log::info("[authorWeightChange]: Начинается изменение веса автора в совпадениях");

        DB::table('book_matches')
            ->where('author_score', 1)
            ->orderBy('id')
            ->chunk($chunk, function (Collection $data) use ($chunk, $save) {
                Log::info("[authorWeightChange]: Получена пачка в $chunk записей");

                $data = $data->map(function ($bookMatch) {
                    $bookMatch->total_score -= 60;
                    return (array) $bookMatch;
                });

                Log::info("[authorWeightChange]: Данные преобразованы, начинается обновление");

                $data->chunk($save)->each(function (Collection $chunked) use ($save) {
                    DB::table('book_matches')->upsert($chunked->toArray(), 'id');
                    Log::info("[authorWeightChange]: Обновлено $save записей");
                });

                Log::info("[authorWeightChange]: Данные обновлены");
            });

        Log::info("[authorWeightChange]: Изменение веса автора в сопадениях завершено");
    }
}
