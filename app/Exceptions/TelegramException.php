<?php

namespace App\Exceptions;

use Exception;
use Telegram\Bot\Objects\Update;

class TelegramException extends Exception
{
    public Update $update;
    public string $telegramText;

    public function __construct(string $message, $code, Update $update)
    {
        $this->update = $update;

        $this->telegramText = $message;
        $this->telegramText .= "\n\n";
        $this->telegramText .= "```" . json_encode($update) . "```";

        parent::__construct($message, (int) $code);
    }
}
