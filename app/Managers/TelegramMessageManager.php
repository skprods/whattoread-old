<?php

namespace App\Managers;

use App\Models\TelegramMessage;
use App\Models\TelegramUser;

class TelegramMessageManager
{
    protected ?TelegramMessage $telegramMessage;

    public function __construct(TelegramMessage $telegramMessage = null)
    {
        $this->telegramMessage = $telegramMessage;
    }

    public function create(array $params, TelegramUser $telegramUser): TelegramMessage
    {
        $this->telegramMessage = app(TelegramMessage::class);
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
