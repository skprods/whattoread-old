<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
class BookRecs extends Model
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

        static::saving(function (BookRecs $model) {
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
        return self::getAuthorScore($value);
    }

    /** Обычное значение очков автора */
    public static function getAuthorScore($value): int
    {
        return $value * 40;
    }

    /** Значение очков автора для БД */
    public static function getAuthorDbValue($value): int
    {
        if ($value > 1) {
            $score = 1;
        } elseif ($value < 0) {
            $score = 0;
        } else {
            $score = $value;
        }

        return $score;
    }

    public function setAuthorScoreAttribute($value)
    {
        $this->attributes['author_score'] = self::getAuthorDbValue($value);
    }

    public function getGenresScoreAttribute($value): int
    {
        return self::getGenresScore($value);
    }

    /** Обычное значение очков жанров */
    public static function getGenresScore($value): int
    {
        return $value * 10;
    }

    /** Значение очков жанров для БД */
    public static function getGenresDbValue($value): int
    {
        if ($value > 4) {
            $score = 4;
        } elseif ($value < 0) {
            $score = 0;
        } else {
            $score = $value;
        }

        return $score;
    }

    public function setGenresScoreAttribute($value)
    {
        $this->attributes['genres_score'] = self::getGenresDbValue($value);
    }

    public static function firstByBookIds(int $firstBookId, int $secondBookId): ?BookRecs
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

    public static function getByBookId(int $bookId): Collection
    {
        return self::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->get();
    }

    public static function deleteByBookId(int $bookId)
    {
        return self::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->delete();
    }
}
