<?php

namespace App\Services\Frequencies;

use App\Models\Book;
use App\Models\BookFrequencies\FrequenciesModelManager;
use Illuminate\Support\Collection;

class BookFrequenciesService
{
    private FrequenciesModelManager $manager;

    public function __construct()
    {
        $this->manager = app(FrequenciesModelManager::class);
    }

    public function addDescriptionFrequencies(Collection $frequencies, Book $book): int
    {
        return $this->addFrequencies($frequencies, $book, $this->manager::DESCRIPTION_MODEL);
    }

    public function addContentFrequencies(Collection $frequencies, Book $book): int
    {
        return $this->addFrequencies($frequencies, $book, $this->manager::CONTENT_MODEL);
    }

    private function addFrequencies(Collection $frequencies, Book $book, string $model): int
    {
        $data = $frequencies->map(function ($frequency, $wordId) use ($book) {
            return [
                'word_id' => $wordId,
                'book_id' => $book->id,
                'frequency' => $frequency,
            ];
        });

        $this->manager->model($model)::query()->insert($data->toArray());

        return $data->count();
    }

    public function deleteDescriptionFrequency(Book $book)
    {
        return $this->deleteFrequency($book, $this->manager::DESCRIPTION_MODEL);
    }

    public function deleteContentFrequency(Book $book)
    {
        return $this->deleteFrequency($book, $this->manager::CONTENT_MODEL);
    }

    private function deleteFrequency(Book $book, string $model)
    {
        return $this->manager->model($model)::query()->where('book_id', $book->id)->delete();
    }
}