<?php

namespace App\Telegram\Commands;

use App\Telegram\TelegramCommand;
use App\Traits\HasCommandsList;

class StartCommand extends TelegramCommand
{
    use HasCommandsList;

    public string $name = 'start';
    public string $description = "Запуск бота";

    protected function handle()
    {
        $commands = array_merge($this->telegram->commands, $this->telegram->dialogs);

        $text = "Привет! Здесь ты можешь найти книги, которые тебе понравятся.\n\n";
        $text .= $this->getCommandsMessage($commands);

        $this->replyWithMessage(['text' => $text]);
    }
}
