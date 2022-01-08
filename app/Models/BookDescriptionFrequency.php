<?php

namespace App\Models;

use Carbon\Carbon;
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

    public static function getBookFrequenciesByWordIds(array $wordIds): array
    {
        $builder = self::query()->select('book_id');

        foreach ($wordIds as $wordId) {
            $builder->orWhere('word_id', $wordId);
        }

        return self::query()
            ->select()
            ->whereIn('book_id', $builder)
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
            })
            ->toArray();
    }
}