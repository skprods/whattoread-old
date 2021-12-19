<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasDatabaseCounter;

    protected $fillable = [
        'command',
        'message',
        'responses',
    ];

    protected $casts = [
        'responses' => 'json',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function telegramChat(): BelongsTo
    {
        return $this->belongsTo(TelegramChat::class);
    }
}
