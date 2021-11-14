<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class HelpCommand extends TelegramCommand
{
    protected $name = 'help';

    protected $aliases = ['list'];

    protected $description = 'Получить список команд';

    public function handleCommand()
    {
        $commands = $this->telegram->getCommands();

        $text = 'Вот доступные команды: \n";';
        $text .= $this->getStartCommand($commands);
        $text .= $this->getHelpCommand($commands);
        $text .= "\n";

        foreach ($commands as $name => $handler) {
            /* @var Command $handler */
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(['text' => $text]);
    }

    private function getStartCommand(&$commands): string
    {
        $start = $commands['start'];
        unset($commands['start']);
        return sprintf('/%s - %s' . PHP_EOL, 'start', $start->getDescription());
    }

    private function getHelpCommand(&$commands): string
    {
        $start = $commands['help'];
        unset($commands['help']);
        return sprintf('/%s - %s' . PHP_EOL, 'help', $start->getDescription());
    }
}
