<?php

namespace App\Telegram\Commands;

class StartCommand extends TelegramCommand
{
    protected $name = 'start';

    protected $description = "Запуск бота";

    public function handleCommand()
    {
        $commands = $this->getTelegram()->getCommands();

        $response = "Привет! Здесь ты можешь найти книги, которые тебе понравятся. \n\nВот доступные команды: \n";
        $response .= $this->getStartCommand($commands);
        $response .= $this->getHelpCommand($commands);
        $response .= "\n";

        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        $this->replyWithMessage(['text' => $response]);
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
