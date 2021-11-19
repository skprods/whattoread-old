<?php

namespace App\Models;

use App\Traits\HasDiffCount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TelegramUser extends Model
{
    use HasDiffCount;

    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'username',
    ];

    public function telegramMessages(): HasMany
    {
        return $this->hasMany(TelegramMessage::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(TelegramUserBook::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public static function getUserByTelegramId(int $telegramId): TelegramUser|null
    {
        return self::query()->where('telegram_id', $telegramId)->first();
    }
}
