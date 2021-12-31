<?php

namespace App\Models\Vectors;

use App\Models\Book;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Book $book
 * @property array $vector
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class BookVector extends Model
{
    protected $fillable = [
        'vector',
    ];

    protected $casts = [
        'vector' => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public static function findByBookId(int $bookId): ?BookVector
    {
        return self::query()->where('book_id', $bookId)->first();
    }
}
