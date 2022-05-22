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

    /** Поиск словарей для книг */
    public static function findByBookIds(array $bookIds): Collection
    {
        return self::query()->whereIn('book_id', $bookIds)->get();
    }

    /** Удаление словарей для книг */
    public static function deleteByBookIds(array $bookIds)
    {
        return self::query()->whereIn('book_id', $bookIds)->delete();
    }
}
