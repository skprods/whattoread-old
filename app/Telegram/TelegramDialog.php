<?php

namespace App\Telegram;

use App\Exceptions\TelegramException;
use App\Managers\ExceptionManager;
use App\Managers\TelegramChatManager;
use App\Managers\TelegramMessageManager;
use App\Managers\TelegramUserManager;
use App\Models\TelegramChat;
use App\Models\TelegramUser;
use App\Services\NotificationService;
use App\Traits\HasCallbackData;
use App\Traits\HasKeyboard;
use Exception;
use Illuminate\Support\Facades\Log;
use SKprods\Telegram\Core\Dialog;
use SKprods\Telegram\Objects\Chat\Chat;

abstract class TelegramDialog extends Dialog
{
    use HasCallbackData;
    use HasKeyboard;

    /** Нужно ли отображать команду в списке команд */
    public bool $show = true;

    protected ?Chat $chat = null;

    protected ?TelegramUser $telegramUser;
    protected ?TelegramChat $telegramChat = null;
    protected TelegramMessageManager $telegramMessageManager;

    private array $responses = [];

    public function __construct()
    {
        $this->telegramMessageManager = app(TelegramMessageManager::class);
    }

    protected function beforeHandle()
    {
        $this->logUser();
        $this->logMessage();
    }

    protected function afterHandle()
    {
        $this->logResponses($this->responses);
    }

    protected function handleException(Exception $exception)
    {
        $notificationService = app(NotificationService::class, ['telegram' => $this->telegram]);
        $e = new TelegramException($exception, $this->update);

        if (env('APP_ENV') === 'production') {
            $notificationService->notifyForTelegramException($e);
        }

        try {
            app(ExceptionManager::class)->create([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            Log::error(json_encode($exception, JSON_UNESCAPED_UNICODE));
        } catch (Exception $exception) {
            Log::error(json_encode($exception, JSON_UNESCAPED_UNICODE));
        }

        $this->telegram->sendMessage([
            'chat_id' => $e->update->getChat()->id,
            'text' => "Что-то пошло не так... Наши администраторы уже в курсе, скоро мы всё исправим.\nПожалуйста, попробуйте чуть позже."
        ]);

        throw $e;
    }

    protected function getChat(): Chat
    {
        if (!$this->chat) {
            $this->chat = $this->update->getChat();
        }

        return $this->chat;
    }

    protected function logUser()
    {
        $chat = $this->getChat();

        if ($chat->type === 'private') {
            $this->telegramUser = app(TelegramUserManager::class)->createOrUpdate([
                'telegram_id' => $chat->id,
                'first_name' => $chat->firstName,
                'last_name' => $chat->lastName,
                'username' => $chat->username ?? $chat->id,
                'status' => TelegramUser::ACTIVE_STATUS,
            ]);
        } else {
            $this->telegramUser = app(TelegramUserManager::class)->createOrUpdate([
                'telegram_id' => $this->update->message->from->id,
                'first_name' => $this->update->message->from->firstName,
                'last_name' => $this->update->message->from->lastName,
                'username' => $this->update->message->from->username,
            ]);

            $this->telegramChat = app(TelegramChatManager::class)->createOrUpdate([
                'chat_id' => $chat->id,
                'title' => $chat->title,
            ]);
        }
    }

    private function logMessage()
    {
        $user = TelegramUser::findByTelegramId($this->chatInfo->id);

        $this->telegramMessageManager->create([
            'command' => $this->chatInfo->currentCommand->toArray(),
            'message' => optional($this->update->getMessage())->text,
        ], $user);
    }

    public function logResponses(array $responses)
    {
        $this->telegramMessageManager->update(['responses' => $responses]);
    }

    protected function addResponse(array $response)
    {
        $this->responses[] = $response;
    }

    protected function replyWithMessage(array $sendMessageParams)
    {
        $data = array_merge($sendMessageParams, ['chat_id' => $this->chatInfo->id]);

        $this->addResponse($data);
        $this->telegram->sendMessage($data);
    }

    public function editMessageText(array $params): \SKprods\Telegram\Objects\Message|bool
    {
        $this->addResponse($params);

        try {
            return $this->telegram->editMessageText($params);
        } catch (\Exception $exception) {
            $this->logError($params, $exception);
            throw $exception;
        }
    }
}
