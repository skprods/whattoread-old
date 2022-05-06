<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $word
 * @property int $parent_id
 * @property-read  Word|null $parent
 * @property string $type
 * @property array $vector
 * @property Carbon $created_at
 */
class Word extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

    public const PARTICLE_TYPE = 'част';
    public const INTERJECTION_TYPE = 'межд';
    public const ADJECTIVE_TYPE = 'прл';
    public const PARTICIPLE_TYPE = 'прч';
    public const NOUN_TYPE = 'сущ';
    public const ADVERB_TYPE = 'нар';
    public const VERB_TYPE = 'гл';
    public const ADVERB_PARTICIPLE_TYPE = 'дееп';
    public const UNION_TYPE = 'союз';
    public const PREDICATE_TYPE = 'предик';
    public const PREPOSITION_TYPE = 'предл';
    public const INTRODUCTORY_TYPE = 'ввод';
    public const PRONOUN_TYPE = 'мест';
    public const NUMERAL_TYPE = 'числ';
    public const PHRASEOLOGY_TYPE = 'фраз';
    public const PROPER_NOUN_TYPE = 'собс'; // имя собственное/нарицательное

    public const TYPES = [
        self::PARTICLE_TYPE,
        self::INTERJECTION_TYPE,
        self::ADJECTIVE_TYPE,
        self::PARTICIPLE_TYPE,
        self::NOUN_TYPE,
        self::ADVERB_TYPE,
        self::VERB_TYPE,
        self::ADVERB_PARTICIPLE_TYPE,
        self::UNION_TYPE,
        self::PREDICATE_TYPE,
        self::PREPOSITION_TYPE,
        self::INTRODUCTORY_TYPE,
        self::PRONOUN_TYPE,
        self::NUMERAL_TYPE,
        self::PHRASEOLOGY_TYPE,
    ];

    public $timestamps = false;

    protected $fillable = [
        'word',
        'parent_id',
        'type',
        'vector',
        'created_at',
    ];

    protected $casts = [
        'vector' => 'array',
        'created_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'parent_id');
    }

    public static function getByIds(array $ids): Collection
    {
        return self::query()->whereIn('id', $ids)->get();
    }
}
