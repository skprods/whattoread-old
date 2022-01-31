<?php

namespace App\Telegram\Commands;

use App\Telegram\TelegramCommand;
use App\Traits\HasCommandsList;

class HelpCommand extends TelegramCommand
{
    use HasCommandsList;

    public string $name = 'help';
    public array $aliases = ['list'];
    public string $description = 'Список команд';

    protected function handle()
    {
        $commands = array_merge($this->telegram->commands, $this->telegram->dialogs);
        $text = $this->getCommandsMessage($commands);

        $this->replyWithMessage(['text' => $text]);
    }
}
