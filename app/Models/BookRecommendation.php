<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $comparing_book_id
 * @property-read Book $comparingBook
 * @property int $matching_book_id
 * @property-read Book $matchingBook
 * @property int $author_score
 * @property int $genres_score
 * @property double $description_score
 * @property double $total_score
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookRecommendation extends Model
{
    use HasDatabaseCounter;

    protected $fillable = [
        'author_score',
        'genres_score',
        'description_score',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (BookRecommendation $model) {
            $author = $model->author_score ?? 0;
            $genres = $model->genres_score ?? 0;
            $description = $model->description_score ?? 0;

            $model->total_score = $author + $genres + $description;
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

    public function getGenresScoreAttribute($value): int
    {
        return $value * 10;
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

    public function setGenresScoreAttribute($value)
    {
        if ($value > 4) {
            $score = 4;
        } elseif ($value < 0) {
            $score = 0;
        } else {
            $score = $value;
        }

        $this->attributes['genres_score'] = $score;
    }

    public static function firstByBookIds(int $firstBookId, int $secondBookId): ?BookRecommendation
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
