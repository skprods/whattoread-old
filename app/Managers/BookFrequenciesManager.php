<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\BookContentFrequency;
use App\Models\BookDescriptionFrequency;
use Illuminate\Support\Collection;

class BookFrequenciesManager
{
    public function addContentFrequencies(Collection $frequencies, Book $book): int
    {
        $data = $frequencies->map(function ($frequency, $wordId) use ($book) {
            return [
                'word_id' => $wordId,
                'book_id' => $book->id,
                'frequency' => $frequency,
            ];
        });

        BookContentFrequency::query()->insert($data->toArray());

        return $data->count();
    }

    public function deleteContentFrequency(Book $book)
    {
        BookContentFrequency::query()->where('book_id', $book->id)->delete();
    }

    public function addDescriptionFrequencies(Collection $frequencies, Book $book): int
    {
        $data = $frequencies->map(function ($frequency, $wordId) use ($book) {
            return [
                'word_id' => $wordId,
                'book_id' => $book->id,
                'frequency' => $frequency,
            ];
        });

        BookDescriptionFrequency::query()->insert($data->toArray());

        return $data->count();
    }

    public function deleteDescriptionFrequency(Book $book)
    {
        BookDescriptionFrequency::query()->where('book_id', $book->id)->delete();
    }
}