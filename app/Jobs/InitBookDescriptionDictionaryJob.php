<?php

namespace App\Jobs;

use App\Services\Database\Frequencies\FrequencyService;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

class InitBookDescriptionDictionaryJob extends Job
{
    private ?int $start;
    private ?int $end;

    private FrequencyService $manager;

    public function __construct(?int $start, ?int $end, bool $debug)
    {
        $this->start = $start;
        $this->end = $end;
        $this->manager = app(FrequencyService::class);

        parent::__construct($debug);
    }

    public function handle()
    {
        $builder = Book::query()->select()->where('status', Book::ACTIVE_STATUS)->orderBy('id');

        if ($this->start) {
            $builder->where('id', '>=', $this->start);
        }

        if ($this->end) {
            $builder->where('id', '<=', $this->end);
        }

        $builder->chunk(10000, function (Collection $data) {
            $data->each(function (Book $book) {
                $this->manager->createDescriptionFrequency($book);
            });
        });
    }
}
