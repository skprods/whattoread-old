<?php

namespace App\Exceptions;

use Exception;
use SKprods\Telegram\Objects\Update;

class TelegramException extends Exception
{
    public Update $update;
    private string $telegramText;
    private Exception $baseException;

    public function __construct(Exception $baseException, Update $update)
    {
        $this->baseException = $baseException;
        $this->update = $update;

        $this->telegramText = $baseException->message;
        $this->telegramText .= "\n\n";
        $this->telegramText .= json_encode($update, JSON_UNESCAPED_UNICODE);

        parent::__construct($baseException->getMessage(), (int) $baseException->getCode());
    }

    public function getText(): string
    {
        return $this->telegramText;
    }

    public function getBaseException(): Exception
    {
        return $this->baseException;
    }
}
