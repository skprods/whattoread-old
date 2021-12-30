<?php

namespace App\Models\WordVectors;

use App\Models\Word;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Word $word
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
}
