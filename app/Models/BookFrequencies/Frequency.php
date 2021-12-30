<?php

namespace App\Models\BookFrequencies;

use App\Models\Book;
use App\Models\Word;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Book $book
 * @property-read Word $word
 * @property double $frequency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class Frequency extends Model
{
    protected $fillable = [
        'frequency',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public static function getByWordId(int $wordId, int $limit = 50): Collection
    {
        return static::query()
            ->where('word_id', $wordId)
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get();
    }
}
