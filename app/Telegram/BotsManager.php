<?php

namespace App\Telegram;

use Telegram\Bot\Exceptions\TelegramSDKException;

class BotsManager extends \Telegram\Bot\BotsManager
{
    public function bot($name = null): Telegram
    {
        $name = $name ?? $this->getDefaultBotName();

        if (! isset($this->bots[$name])) {
            $this->bots[$name] = $this->makeBot($name);
        }

        return $this->bots[$name];
    }
    /**
     * Make the bot instance.
     *
     * @param string $name
     *
     * @throws TelegramSDKException
     * @return Telegram
     */
    protected function makeBot($name): Telegram
    {
        $config = $this->getBotConfig($name);

        $token = data_get($config, 'token');

        $telegram = new Telegram(
            $token,
            $this->getConfig('async_requests', false),
            $this->getConfig('http_client_handler', null)
        );

        $commands = data_get($config, 'commands', []);
        $commands = $this->parseBotCommands($commands);

        // Register Commands
        $telegram->addCommands($commands);
        $telegram->config = $config;

        return $telegram;
    }
}