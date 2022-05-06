<?php

namespace App\Models;

use App\Traits\HasDatabaseBooksCounter;
use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Book $book
 * @property int $book_id
 * @property-read Word $word
 * @property int $word_id
 * @property double $frequency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookDescriptionFrequency extends Model
{
    use HasDatabaseCounter;
    use HasDatabaseBooksCounter;

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

    /**
     * @param array $wordIds        - ID слов, по которым ищутся книги
     * @param array $onlyBookIds    - ID книг, из которых должна состоять выборка (для ограничений по жанру)
     * @return \Illuminate\Support\Collection
     */
    public static function getBookIdsForRecs(array $wordIds, array $onlyBookIds = []): \Illuminate\Support\Collection
    {
        $builder = self::query()->select('book_id')->groupBy('book_id');

        foreach ($wordIds as $wordId) {
            $builder->orWhere('word_id', $wordId);
        }

        if (!empty($onlyBookIds)) {
            $builder->whereIn('book_id', $onlyBookIds);
        }

        return $builder->get()->pluck('book_id')->mapWithKeys(function ($bookId) {
            return [$bookId => $bookId];
        });
    }

    public static function getBookFrequenciesByBookIds(array $bookIds): \Illuminate\Support\Collection
    {
        return self::query()
            ->select()
            ->whereIn('book_id', $bookIds)
            ->orderByDesc('frequency')
            ->get()
            ->mapToGroups(function (BookDescriptionFrequency $frequency) {
                return [
                    $frequency->book_id => [
                        'word_id' => $frequency->word_id,
                        'frequency' => $frequency->frequency,
                    ]
                ];
            })
            ->map(function (Collection $item) {
                return $item->pluck('frequency', 'word_id');
            });
    }
}
