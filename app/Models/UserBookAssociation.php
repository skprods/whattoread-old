<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBookAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'association',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public static function checkExists(
        string $association,
        int $bookId,
        int $userId = null,
        int $telegramUserId = null
    ): ?UserBookAssociation {
        $builder = self::query()->where('association', $association)->where('book_id', $bookId);

        if ($userId) {
            $builder->where('user_id', $userId);
        }

        if ($telegramUserId) {
            $builder->where('telegram_user_id', $telegramUserId);
        }

        return $builder->first();
    }
}
