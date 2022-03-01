<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $book_id
 * @property-read Book $book
 * @property array $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookRecsShort extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'data',
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public static function find(int $bookId): ?self
    {
        return self::query()->where('book_id', $bookId)->first();
    }

    public function getMatchingBooks(): \Illuminate\Database\Eloquent\Collection
    {
        $bookIds = array_keys($this->data);
        return Book::whereIdIn($bookIds);
    }
}
