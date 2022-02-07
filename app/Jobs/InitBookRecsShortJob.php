<?php

namespace App\Jobs;

use App\Services\BookRecsShortService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InitBookRecsShortJob extends QueueJob
{
    private ?int $start;
    private ?int $end;
    private BookRecsShortService $service;

    public function __construct(int $start = null, int $end = null, bool $debug = false)
    {
        $this->start = $start;
        $this->end = $end;

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->log('Начинается наполнение быстрых рекомендаций');

        $this->service = app(BookRecsShortService::class, ['debug' => $this->debugMode]);

        $builder = DB::table('books')->orderBy('id');

        if ($this->start) {
            $builder->where('id', '>=', $this->start);
        }

        if ($this->end) {
            $builder->where('id', '<=', $this->end);
        }

        $builder->chunk(1000, function (Collection $data) {
            $bookIds = $data->pluck('id')->toArray();
            $this->service->createForBooks($bookIds);
        });

        $this->log('Наполнение быстрых рекомендаций завершено');
    }
}
