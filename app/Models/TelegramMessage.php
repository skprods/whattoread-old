<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property array $command
 * @property string $message
 * @property array $responses
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TelegramMessage extends Model
{
    use HasDatabaseCounter;

    protected $fillable = [
        'command',
        'message',
        'responses',
    ];

    protected $casts = [
        'command' => 'json',
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
