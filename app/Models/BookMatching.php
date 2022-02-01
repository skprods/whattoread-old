<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Book $comparingBook
 * @property-read Book $matchingBook
 * @property double $author_score
 * @property double $description_score
 * @property double $total_score
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookMatching extends Model
{
    use HasDatabaseCounter;

    protected $table = 'book_matches';

    protected $fillable = [
        'author_score',
        'description_score',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (BookMatching $model) {
            $author = $model->author_score ?? 0;
            $description = $model->description_score ?? 0;

            $model->total_score = $author + $description;
        });
    }

    public function comparingBook(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function matchingBook(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function getAuthorScoreAttribute($value): int
    {
        return $value * 40;
    }

    public function setAuthorScoreAttribute($value)
    {
        if ($value > 1) {
            $score = 1;
        } elseif ($value < 0) {
            $score = 0;
        } else {
            $score = $value;
        }

        $this->attributes['author_score'] = $score;
    }

    public static function firstByBookIds(int $firstBookId, int $secondBookId): ?BookMatching
    {
        return self::query()
            ->where(function (Builder $query) use ($firstBookId, $secondBookId) {
                return $query->where('comparing_book_id', $firstBookId)
                    ->where('matching_book_id', $secondBookId);
            })
            ->orWhere(function (Builder $query) use ($firstBookId, $secondBookId) {
                return $query->where('comparing_book_id', $secondBookId)
                    ->where('matching_book_id', $firstBookId);
            })
            ->first();
    }

    public static function deleteByBookId(int $bookId)
    {
        return self::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->delete();
    }
}
