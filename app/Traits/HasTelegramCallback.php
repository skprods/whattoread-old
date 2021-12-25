<?php

namespace App\Traits;

use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Chat;

trait HasTelegramCallback
{
    public ?CallbackQuery $callbackQuery = null;

    public function setCallbackProperties(CallbackQuery $callbackQuery, ?Chat $chat)
    {
        $this->setCallbackQuery($callbackQuery);
        $this->setChat($chat);
    }

    protected function setChat(?Chat $chat)
    {
        $this->chat = $chat;
    }

    protected function setCallbackQuery(CallbackQuery $callbackQuery)
    {
        $this->callbackQuery = $callbackQuery;
    }
}