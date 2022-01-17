<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $book_id
 * @property-read Book $book
 * @property int $telegram_user_id
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

    public static function getUserBookIds(int $userId): array
    {
        return self::query()
            ->where('telegram_user_id', $userId)
            ->get()
            ->mapWithKeys(function (TelegramUserBook $telegramUserBook) {
                return [$telegramUserBook->book_id => $telegramUserBook->book_id];
            })
            ->toArray();
    }
}
