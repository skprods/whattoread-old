<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $telegram_id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TelegramUser extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

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

    public static function findByTelegramId(int $telegramId): TelegramUser|null
    {
        return self::query()->where('telegram_id', $telegramId)->first();
    }
}
