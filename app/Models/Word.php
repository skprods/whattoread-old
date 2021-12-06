<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function parentWord(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'parent_id');
    }
}
