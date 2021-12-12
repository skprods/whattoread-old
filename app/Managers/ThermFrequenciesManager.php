<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\ThermFrequency;
use Illuminate\Support\Collection;

class ThermFrequenciesManager
{
    public function bulkCreate(Collection $frequencies, Book $book)
    {
        $data = $frequencies->map(function ($frequency, $wordId) use ($book) {
            return [
                'word_id' => $wordId,
                'book_id' => $book->id,
                'frequency' => $frequency,
            ];
        });

        ThermFrequency::query()->insert($data->toArray());
    }

    public function deleteForBook(Book $book)
    {
        ThermFrequency::query()->where('book_id', $book->id)->delete();
    }
}