<?php

namespace App\Services;

use App\Exceptions\TelegramException;
use Telegram\Bot\Api;
use Throwable;

class NotificationService
{
    private Api $telegram;

    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function notifyForException(Throwable $e)
    {
        $text = $this->prepareResponse($e->getCode(), $e->getMessage(), get_class($e));

        $this->sendMessage($text);
    }

    public function notifyForTelegramException(TelegramException $e)
    {
        $text = $this->prepareResponse($e->getCode(), $e->getText(), get_class($e->getBaseException()));

        $this->sendMessage($text);
    }

    private function prepareResponse(int $code, string $message, string $errorClass): string
    {
        $text = "*ОШИБКА*:\n";
        $text .= "Код: $code\n";
        $text .= "Класс: $errorClass\n";
        $text .= "\n";
        $text .= $message;

        return $text;
    }

    private function sendMessage(string $text)
    {
        $this->telegram->sendMessage([
            'chat_id' => env('ERROR_CHAT_ID'),
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }
}
