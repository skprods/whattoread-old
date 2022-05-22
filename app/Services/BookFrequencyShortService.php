<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookDescriptionFrequency;
use App\Models\BookDescriptionFrequencyShort;
use Illuminate\Support\Collection;

class BookFrequencyShortService extends Service
{
    public function init(int $startBookId = null, int $endBookId = null, int $chunk = null)
    {
        $this->log("Начинается составление быстрого частотного словника.");
        $startIdMessage = $startBookId ?? "не указан";
        $this->log("Стартовый ID: $startIdMessage");
        $endIdMessage = $endBookId ?? "не указан";
        $this->log("Конечный ID: $endIdMessage");

        $chunk = $chunk ?? 10000;
        $this->bar->start();

        Book::builderWithStartEnd($startBookId, $endBookId)
            ->select('id')
            ->chunk($chunk, function (Collection $data) use ($chunk) {
                $this->createForBooks($data->pluck('id')->toArray());
            });

        $this->bar->finish();
        $this->consoleNewLine();
        $this->log("Быстрый словник успешно составлен.");
    }

    /**
     * Создание быстрых словников по описанию книги
     */
    public function createForBooks(array $bookIds): void
    {
        BookDescriptionFrequencyShort::deleteByBookIds($bookIds);
        $frequencies = BookDescriptionFrequency::getBookFrequenciesByBookIds($bookIds);

        $data = [];
        $frequencies->each(function (Collection $frequencies, int $bookId) use (&$data) {
            $data[] = [
                'book_id' => $bookId,
                'data' => $frequencies->toJson(),
            ];
        });

        BookDescriptionFrequencyShort::createMany($data);
        $this->bar->advance(count($bookIds));
    }
}