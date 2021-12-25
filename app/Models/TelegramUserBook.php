<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Book $book
 * @property-read TelegramUser $telegramUser
 * @property int $rating
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TelegramUserBook extends Model
{
    protected $fillable = [
        'rating',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
