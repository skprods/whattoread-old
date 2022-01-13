<?php

namespace App\Telegram;

use Telegram\Bot\Api;

class Telegram extends Api
{
    public array $config = [];

    /**
     * Return Command Bus.
     *
     * @return CommandBus
     */
    protected function getCommandBus(): CommandBus
    {
        return CommandBus::Instance()->setTelegram($this);
    }
}