<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $author
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|Genre[] $genres
 * @property-read Collection|Category[] $categories
 * @property-read Collection|TelegramUser[] $telegramUsers
 * @property-read Collection|BookAssociation[] $associations
 */
class Book extends Model
{
    use HasFactory;

    public const MODERATION_STATUS = 'moderation';
    public const ACTIVE_STATUS = 'active';

    protected $fillable = [
        'title',
        'description',
        'author',
    ];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function telegramUsers(): HasMany
    {
        return $this->hasMany(TelegramUserBook::class);
    }

    public function associations(): HasMany
    {
        return $this->hasMany(BookAssociation::class);
    }
}
