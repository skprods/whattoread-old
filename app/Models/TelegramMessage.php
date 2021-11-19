<?php

namespace App\Models;

use App\Traits\HasDiffCount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    use HasDiffCount;

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
}
