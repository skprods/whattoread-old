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

    public function notify(string $message)
    {
        $text = "*УВЕДОМЛЕНИЕ*:\n\n";
        $text .= $message;

        $this->sendMessage($text);
    }

    public function notifyForException(Throwable $e)
    {
        $text = $this->prepareResponse($e, $e->getMessage(), get_class($e));

        $this->sendMessage($text);
    }

    public function notifyForTelegramException(TelegramException $e)
    {
        $text = $this->prepareResponse($e, $e->getText(), get_class($e->getBaseException()));

        $this->sendMessage($text);
    }

    private function prepareResponse(Throwable $e, string $message, string $errorClass): string
    {
        $text = "*ОШИБКА*:\n";
        $text .= "Код: {$e->getCode()}\n";
        $text .= "Класс: $errorClass\n";
        $text .= "\n";
        $text .= "Файл: {$e->getFile()}\n";
        $text .= "Линия: {$e->getLine()}\n";
        $text .= "\n";

        $message = str_replace('_', '\\_', $message);
        $message = str_replace('*', '\\*', $message);
        $message = str_replace('[', '\\[', $message);
        $message = str_replace('`', '\\`', $message);

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
