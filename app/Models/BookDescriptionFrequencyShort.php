<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $book_id
 * @property-read Book $book
 * @property array $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookDescriptionFrequencyShort extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'book_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Поиск словарей для книг
     *
     * Результатом функции будет коллекция в формате
     * [ bookId => [word1 => frequency, word2 => frequency] ]
     */
    public static function findByBookIds(array $bookIds): Collection
    {
        $chunked = array_chunk($bookIds, 10000);
        $data = new Collection();

        foreach ($chunked as $books) {
            $frequencies = self::query()
                ->whereIn('book_id', $books)
                ->get()
                ->mapWithKeys(function (self $frequencyShort) {
                    return [$frequencyShort->book_id => $frequencyShort->data];
                });

            $data = $data->union($frequencies);
        }

        return $data;
    }

    /** Удаление словарей для книг */
    public static function deleteByBookIds(array $bookIds)
    {
        return self::query()->whereIn('book_id', $bookIds)->delete();
    }
}
