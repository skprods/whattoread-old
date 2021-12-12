<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $word
 * @property-read  Word|null $parentWord
 * @property string $type
 * @property string $type_sub
 * @property string $type_ssub
 * @property boolean $plural
 * @property string $gender
 * @property string $wcase
 * @property string $comp
 * @property boolean $soul
 * @property string $transit
 * @property boolean $perfect
 * @property string $face
 * @property string $kind
 * @property string $time
 * @property boolean $inf
 * @property boolean $vozv
 * @property string $nakl
 * @property boolean $short
 * @property Carbon $created_at
 */
class Word extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'word',
        'type',
        'type_sub',
        'type_ssub',
        'plural',
        'gender',
        'wcase',
        'comp',
        'soul',
        'transit',
        'perfect',
        'face',
        'kind',
        'time',
        'inf',
        'vozv',
        'nakl',
        'short',
        'created_at',
    ];

    protected $casts = [
        'plural' => 'boolean',
        'soul' => 'boolean',
        'prefect' => 'boolean',
        'inf' => 'boolean',
        'vozv' => 'boolean',
        'short' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function parentWord(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'parent_id');
    }
}
