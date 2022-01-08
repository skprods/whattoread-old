<?php

namespace App\Jobs;

use App\Managers\Dictionaries\FrequencyManager;
use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

class InitBookDescriptionDictionaryJob extends Job
{
    private ?int $start;
    private ?int $end;

    private FrequencyManager $manager;

    public function __construct(?int $start, ?int $end, bool $debug)
    {
        $this->start = $start;
        $this->end = $end;
        $this->manager = app(FrequencyManager::class, ['debug' => $debug]);

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->log('Начинается наполнение частотного словника на основе описаний книг');

        $builder = Book::query()->select()->where('status', Book::ACTIVE_STATUS)->orderBy('id');

        if ($this->start) {
            $builder->where('id', '>=', $this->start);
        }

        if ($this->end) {
            $builder->where('id', '<=', $this->end);
        }

        $builder->chunk(100, function (Collection $data) {
            $data->each(function (Book $book) {
                $this->manager->createDescriptionFrequency($book);
            });
        });

        $this->log('Наполнение частотного словника на основе описаний книг завершено');
    }
}