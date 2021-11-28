<?php

namespace App\Exceptions;

use Exception;
use Telegram\Bot\Objects\Update;

class TelegramException extends Exception
{
    public function __construct(string $message, int $code, Update $update)
    {
        $text = $message;
        $text .= "\n\n";
        $text .= "Данные из Telegram:\n```" . json_encode($update) . "```";

        parent::__construct($text, $code);
    }
}
