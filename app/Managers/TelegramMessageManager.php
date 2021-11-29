<?php

namespace App\Managers;

use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

class TelegramMessageManager
{
    protected ?TelegramMessage $telegramMessage;

    public function __construct(TelegramMessage $telegramMessage = null)
    {
        $this->telegramMessage = $telegramMessage;
    }

    public function create(array $params, TelegramUser $telegramUser, TelegramChat $telegramChat = null): TelegramMessage
    {
        $this->telegramMessage = app(TelegramMessage::class);

        if ($telegramChat) {
            $this->telegramMessage->telegramChat()->associate($telegramChat);
        }

        $this->telegramMessage->telegramUser()->associate($telegramUser);
        $this->telegramMessage->fill($params);
        $this->telegramMessage->save();

        return $this->telegramMessage;
    }

    public function update(array $params): TelegramMessage
    {
        $this->telegramMessage->fill($params);
        $this->telegramMessage->save();

        return $this->telegramMessage;
    }
}
