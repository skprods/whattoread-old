<?php

namespace App\Http\Commands\WhatToReadBot;

use App\Http\Commands\TelegramCommand;

class StartCommand extends TelegramCommand
{
    protected $name = 'start';

    protected $description = "Запускай бота для подбора книжек!";

    public function handle()
    {
        $this->logUser();

        $commands = $this->getTelegram()->getCommands();

        $response = "Привет! Здесь ты можешь найти, что интересного можно почитать. \n\nВот доступные команды: \n";
        $response .= $this->getStartCommand($commands);
        $response .= $this->getHelpCommand($commands);
        $response .= "\n";

        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        $message = ['text' => $response];
        $this->replyWithMessage($message);
        $this->logMessage([
            ['text' => $response],
        ]);
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
