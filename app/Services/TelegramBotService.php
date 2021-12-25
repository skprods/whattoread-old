<?php

namespace App\Services;

use App\Exceptions\TelegramException;
use Exception;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Update;

class TelegramBotService
{
    private BotsManager $telegram;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->telegram = app(BotsManager::class);
        $this->notificationService = app(NotificationService::class, [
            'telegram' => $this->telegram->bot()
        ]);
    }

    public function handle(): string
    {
        try {
            /**
             * Обработка входящих сообщений с помощью обработчика команд.
             * Он автоматически распознаёт сообщения, начинающиеся со /
             * и инициализирует обработку с помощью заранее определённых
             * команд в config/telegram.php
             *
             * @var Update $update
             */
            $update = $this->telegram->commandsHandler(true);

            /**
             * Если в обновлении есть callbackQuery, значит, пришел ответ
             * из клавиатуры у сообщения (inline-keyboard)
             */
            if ($update->callbackQuery) {
                return $this->handleCallback($update);
            }

            /**
             * Если входящее сообщение - не команда, инициализируем диалог.
             * Это кастомный обработчик сообщений, завязанный на Redis
             */
            if (optional($update->message)->text[0] !== '/') {
                DialogService::initDialog($this->telegram, $update);
            }
        } catch (Exception $exception) {
            return $this->handleException($exception);
        }

        return 'ok';
    }

    /** Обработка ответа через inline-keyboard */
    private function handleCallback(Update $update): string
    {
        $callbackData = $update->callbackQuery->data;
        [$commandName, $data] = explode('_', $callbackData);

        $neededCommand = null;
        /** @var Command $command */
        foreach ($this->telegram->getCommands() as $command) {
            if ($command->getName() === $commandName) {
                $neededCommand = $command;
                break;
            }
        }

        if (!$neededCommand) {
            return 'ok';
        }

        $neededCommand->setTelegram($this->telegram->bot());
        $neededCommand->setCallbackProperties($update->callbackQuery, $update->callbackQuery->message->chat);
        $neededCommand->handle();

        return 'ok';
    }

    /** Обработка ошибок - отправка сообщения пользователю и в чат администрации */
    private function handleException(Exception $exception): string
    {
        $e = new TelegramException($exception, $this->telegram->bot()->getWebhookUpdate());

        if (env('APP_ENV') === 'production') {
            $this->notificationService->notifyForTelegramException($e);
        }

        /**
         * В обновлениях не всегда есть чат, иногда бывает информация о статусе бота - заблокировали или нет,
         * тогда getChat() вернёт пустую коллекцию. В таких случаях не нужно отвечать.
         */
        if ($e->update->getChat()->count()) {
            $this->telegram->bot()->sendMessage([
                'chat_id' => $e->update->getChat()->id,
                'text' => "Что-то пошло не так... Наши администраторы уже в курсе, скоро мы всё исправим.\nПожалуйста, попробуйте чуть позже."
            ]);
        }

        return 'ok';
    }
}
