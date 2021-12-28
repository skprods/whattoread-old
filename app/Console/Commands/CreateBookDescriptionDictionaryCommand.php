<?php

namespace App\Console\Commands;

use App\Managers\Dictionaries\FrequencyManager;
use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CreateBookDescriptionDictionaryCommand extends Command
{
    protected $signature = 'dictionary:createFromDescriptions {--start=} {--end=}';

    protected $description = 'Создание частотного словника по описанию книг.';

    private FrequencyManager $manager;

    public function __construct()
    {
        $this->manager = app(FrequencyManager::class);

        parent::__construct();
    }

    public function handle()
    {
        $builder = Book::query()->select()->orderBy('id');

        if ($start = $this->option('start')) {
            $builder->where('id', '>=', $start);
        }

        if ($end = $this->option('end')) {
            $builder->where('id', '<=', $end);
        }

        $builder->chunk(10000, function (Collection $data) {
            $data->each(function (Book $book) {
                $this->manager->createDescriptionFrequency($book);
            });
        });
    }
}
