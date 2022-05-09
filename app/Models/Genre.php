<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property-read Book[] $books
 * @property-read Collection|Genre[] $parents
 * @property-read Collection|Genre[] $childs
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

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'subgenres', 'child_id', 'parent_id');
    }

    public function childs(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'subgenres', 'parent_id', 'child_id');
    }

    public function getParentGenres(Genre $genre = null): Collection
    {
        $genres = new Collection();
        $genre = $genre ?? $this;

        if ($genre->parents->isNotEmpty()) {
            $genre->parents->each(function (Genre $parentGenre) use ($genres) {
                $genres->put($parentGenre->id, $parentGenre);
                $genres->union($this->getParentGenres($parentGenre));
            });
        }

        $genres->put($genre->id, $genre);

        return $genres;
    }
}
