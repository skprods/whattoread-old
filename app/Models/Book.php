<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $author
 * @property string $status
 * @property int|null $words_count
 * @property int $therms_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|Genre[] $genres
 * @property-read Collection|Isbn[] $isbns
 * @property-read Collection|Category[] $categories
 * @property-read Collection|TelegramUser[] $telegramUsers
 * @property-read Collection|BookAssociation[] $associations
 * @property-read Collection|BookContentFrequency[] $contentFrequencies
 * @property-read Collection|BookDescriptionFrequency[] $descriptionFrequencies
 */
class Book extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

    public const MODERATION_STATUS = 'moderation';
    public const ACTIVE_STATUS = 'active';

    public const STATUSES = [
        self::MODERATION_STATUS,
        self::ACTIVE_STATUS,
    ];

    protected $fillable = [
        'title',
        'description',
        'author',
        'status',
        'words_count',
        'therms_count',
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function isbns(): HasMany
    {
        return $this->hasMany(Isbn::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function telegramUsers(): HasMany
    {
        return $this->hasMany(TelegramUserBook::class);
    }

    public function associations(): HasMany
    {
        return $this->hasMany(BookAssociation::class);
    }

    public function userAssociations(): HasMany
    {
        return $this->hasMany(UserBookAssociation::class);
    }

    public function contentFrequencies(): HasMany
    {
        return $this->hasMany(BookContentFrequency::class);
    }

    public function descriptionFrequencies(): HasMany
    {
        return $this->hasMany(BookDescriptionFrequency::class);
    }

    public static function getAuthorsCount(): int
    {
        return self::query()
            ->selectRaw("count(distinct author) as total")
            ->first()
            ->total;
    }

    /** Разница в количестве записей по сравнению с прошлым месяцем (сколько записей в этом месяце) */
    public static function getAuthorsDiffCount(): int
    {
        return self::query()
            ->selectRaw('count(distinct author) as total')
            ->whereRaw("year(curdate()) = year(created_at)")
            ->whereRaw("month(curdate()) = month(created_at)")
            ->first()
            ->total;
    }

    public static function getPaginate(
        int $perPage = 30
    ): LengthAwarePaginator {
        $builder = self::query()
            ->select();

        if (!empty($sort)) {
            $builder->orderBy($sort['field'], $sort['dir']);
        } else {
            $builder->orderBy('id', 'desc');
        }

        return $builder->paginate($perPage, null, null, $page);
    }

    public static function getByBookIds(array $bookIds): Collection
    {
        $builder = self::query()->select();

        foreach ($bookIds as $bookId) {
            $builder->orWhere('id', $bookId);
        }

        return $builder->get();
    }

    public static function whereIdIn(array $bookIds): Collection
    {
        return self::query()->whereIn('id', $bookIds)->get();
    }

    /**
     * Поиск книг, которые входят в определённые жанры
     */
    public static function getBookIdsByGenreIds(array $genreIds): array
    {
        return self::query()
            ->leftJoin('book_genre as bg', 'bg.book_id', '=', 'id')
            ->whereIn('bg.genre_id', $genreIds)
            ->select('id')
            ->get()
            ->pluck('id', 'id')
            ->toArray();
    }
}
