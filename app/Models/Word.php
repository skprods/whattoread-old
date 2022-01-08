<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $word
 * @property-read  Word|null $parentWord
 * @property string $type
 * @property Carbon $created_at
 */
class Word extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

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
