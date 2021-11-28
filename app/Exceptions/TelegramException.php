<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class TelegramException extends Exception
{
    public function __construct(string $message, int $code, Update $update)
    {
        $text = $message;
        $text .= "\n\n";
        Log::info(json_encode($update));
        $text .= "Данные из Telegram:\n```" . json_encode($update) . "```";

        parent::__construct($text, $code);
    }
}
