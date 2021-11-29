<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'title',
    ];

    public static function getByChatId(int $telegramId): TelegramChat|null
    {
        return self::query()->where('chat_id', $telegramId)->first();
    }
}
