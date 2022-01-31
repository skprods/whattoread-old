<?php

namespace App\Traits;

use App\Telegram\Commands\TelegramCommand;
use App\Telegram\Dialogs\TelegramDialog;

trait HasCommandsList
{
    public function getCommandsMessage(array $commands): string
    {
        $text = "Вот доступные команды: \n";
        $text .= $this->getStartCommand($commands);
        $text .= $this->getHelpCommand($commands);
        $text .= "\n";

        foreach ($commands as $name => $handler) {
            /* @var TelegramCommand|TelegramDialog $handler */
            if ($handler->show) {
                $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->description);
            }
        }

        return $text;
    }

    private function getStartCommand(&$commands): string
    {
        $start = $commands['start'];
        unset($commands['start']);
        return sprintf('/%s - %s' . PHP_EOL, 'start', $start->description);
    }

    private function getHelpCommand(&$commands): string
    {
        $start = $commands['help'];
        unset($commands['help']);
        return sprintf('/%s - %s' . PHP_EOL, 'help', $start->description);
    }
}