<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $update_id
 * @property int $telegram_user_id
 * @property-read TelegramUser $telegramUser
 * @property int $book_id
 * @property-read Book $book
 * @property array $recommendations
 * @property int $rating
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RecommendationList extends Model
{
    protected $fillable = [
        'update_id',
        'recommendations',
        'rating',
    ];

    protected $casts = [
        'recommendations' => 'array'
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public static function findByUpdateIdAndTelegramId(int $updateId, int $telegramId): ?self
    {
        return self::query()
            ->where('update_id', $updateId)
            ->where('telegram_user_id', $telegramId)
            ->first();
    }
}
