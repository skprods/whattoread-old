<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class CommandBus extends \Telegram\Bot\Commands\CommandBus
{
    /** @var Telegram $telegram */
    public $telegram;

    /**
     * Execute the command.
     *
     * @param string $name
     * @param Update $update
     * @param array  $entity
     *
     * @return mixed
     */
    protected function execute(string $name, Update $update, array $entity): mixed
    {
        if (!$update->message) {
            return false;
        }

        $command = $this->commands[$name] ?? $this->commandAliases[$name] ?? null;

        if (!$command) {
            foreach ($this->commands as $pattern => $handler) {
                preg_match("/.*{/", $pattern, $shortPattern);
                $shortPattern = count($shortPattern) ? $shortPattern[0] : null;

                if ($shortPattern) {
                    $shortPattern = str_replace('{', '', $shortPattern);
                    preg_match("/\/{$shortPattern}(.*)/", $update->message->text, $matches);
                    preg_match("/{(.*?)}/", $pattern, $params);

                    if (count($matches) === 2) {
                        $command = $handler;
                        $param = $params[1];
                        $value = str_replace("/$shortPattern", '', $update->message->text);
                    }
                }
            }

            if ($command) {
                $args = $command->getArguments();
                $command->setArguments(array_merge($args, [$param => $value]));
            }
        }

        $command = $command ?? $this->commands['help'] ?? null;

        return $command ? $command->make($this->telegram, $update, $entity) : false;
    }
}