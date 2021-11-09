<?php

namespace App\Http\Commands\WhatToReadBot;

use Telegram\Bot\Commands\Command;

class HelpCommand extends TelegramCommand
{
    protected $name = 'help';

    protected $aliases = ['list'];

    protected $description = 'Получить список команд';

    public function handle()
    {
        $this->logUser();

        $commands = $this->telegram->getCommands();

        $text = '';
        foreach ($commands as $name => $handler) {
            /* @var Command $handler */
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(compact('text'));
        $this->logMessage([compact('text')]);
    }
}
