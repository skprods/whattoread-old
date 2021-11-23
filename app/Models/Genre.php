<?php

namespace App\Models;

use App\Traits\HasDiffCount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    use HasFactory;
    use HasDiffCount;

    public const MODERATION_STATUS = 'moderation';
    public const ACTIVE_STATUS = 'active';

    protected $fillable = [
        'name',
        'status',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'parent_id');
    }

    public function child(): HasMany
    {
        return $this->hasMany(Genre::class, 'parent_id');
    }
}
