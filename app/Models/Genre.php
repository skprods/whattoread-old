<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property-read Book[] $books
 * @property int|null $parent_id
 * @property-read Genre|null $parent
 * @property-read Genre|null $child
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Genre extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

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

    public function parent(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'subgenres', 'child_id', 'child_id');
    }

    public function child(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'subgenres', 'parent_id', 'child_id');
    }
}
