<?php

namespace App\Jobs;

use App\Services\BookMatchingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InitBookMatchesJob extends Job
{
    private BookMatchingService $bookMatchingService;

    private ?int $start;
    private ?int $end;

    public function __construct(int $start = null, int $end = null, bool $debug = false)
    {
        $this->start = $start;
        $this->end = $end;

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->log('Начинается наполнение рекомендательной базы книг');

        $this->bookMatchingService = app(BookMatchingService::class, ['debug' => $this->debugMode]);

        $builder = DB::table('books')->orderBy('id');

        if ($this->start) {
            $builder->where('id', '>=', $this->start);
        }

        if ($this->end) {
            $builder->where('id', '<=', $this->end);
        }

        $builder->chunk(100, function (Collection $data) {
            $bookIds = $data->pluck('id')->toArray();
            $this->bookMatchingService->createForBooks($bookIds);
        });

        $this->log('Наполнение рекомендательной базы книг завершено');
    }
}
