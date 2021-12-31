<?php

namespace App\Models\Vectors;

use App\Models\Word;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property-read Word $word
 * @property int $word_id
 * @property array $vector
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class WordVector extends Model
{
    protected $fillable = [
        'vector',
    ];

    protected $casts = [
        'vector' => 'array',
    ];

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public static function findByWordId(int $wordId): ?WordVector
    {
        return self::query()->where('word_id', $wordId)->first();
    }

    public static function getByWordIds(array $wordIds): Collection
    {
        $builder = self::query();

        foreach ($wordIds as $wordId) {
            $builder->orWhere('word_id', $wordId);
        }

        return $builder->get();
    }
}
