<?php

namespace App\Services;

use App\Exceptions\TelegramException;
use App\Managers\ExceptionManager;
use App\Managers\TelegramUserManager;
use App\Models\TelegramUser;
use App\Telegram\BotsManager;
use App\Telegram\Commands\TelegramCommand;
use App\Telegram\Entities\MyChatMember;
use App\Telegram\Telegram;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class TelegramBotService
{
    private Telegram $telegram;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->telegram = app(BotsManager::class)->bot();

        $this->notificationService = app(NotificationService::class, [
            'telegram' => $this->telegram
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

            if ($this->checkKicked($update)) {
                return 'ok';
            }

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

    private function checkKicked(Update $update): bool
    {
        $data = $update->toArray();
        if (!isset($data['my_chat_member'])) {
            return false;
        }

        $chatMember = new MyChatMember($data['my_chat_member']);

        if (
            $chatMember->newChatMember->user->id === config('telegram.bots.whattoread.id')
            && $chatMember->newChatMember->status === TelegramUser::KICKED_STATUS
        ) {
            $telegramUser = TelegramUser::findByTelegramId($chatMember->chat->id);
            app(TelegramUserManager::class, ['telegramUser' => $telegramUser])
                ->update(['status' => TelegramUser::KICKED_STATUS]);

            return true;
        } else {
            return false;
        }
    }

    /** Обработка ответа через inline-keyboard */
    private function handleCallback(Update $update): string
    {
        $callbackData = $update->callbackQuery->data;
        [$commandName, $data] = explode('_', $callbackData);

        $neededCommand = null;
        /** @var TelegramCommand $command */
        foreach ($this->telegram->getCommands() as $command) {
            if ($command->getName() === $commandName) {
                $neededCommand = $command;
                break;
            }
        }

        if (!$neededCommand) {
            return 'ok';
        }

        $neededCommand->setTelegram($this->telegram);
        $neededCommand->setCallbackProperties($update->callbackQuery, $update->callbackQuery->message->chat);
        $neededCommand->handle();

        return 'ok';
    }

    /** Обработка ошибок - отправка сообщения пользователю и в чат администрации */
    private function handleException(Exception $exception): string
    {
        $e = new TelegramException($exception, $this->telegram->getWebhookUpdate());

        if (env('APP_ENV') === 'production') {
            $this->notificationService->notifyForTelegramException($e);
        }

        try {
            app(ExceptionManager::class)->create([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        } catch (Exception $exception) {
            Log::error(json_encode($exception, JSON_UNESCAPED_UNICODE));
        }

        /**
         * В обновлениях не всегда есть чат, иногда бывает информация о статусе бота - заблокировали или нет,
         * тогда getChat() вернёт пустую коллекцию. В таких случаях не нужно отвечать.
         */
        if ($e->update->getChat()->count()) {
            $this->telegram->sendMessage([
                'chat_id' => $e->update->getChat()->id,
                'text' => "Что-то пошло не так... Наши администраторы уже в курсе, скоро мы всё исправим.\nПожалуйста, попробуйте чуть позже."
            ]);
        }

        return 'ok';
    }
}
