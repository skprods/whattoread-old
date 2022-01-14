<?php

namespace App\Telegram\Commands;

use App\Entities\ChatInfo;
use App\Entities\Dialog;
use App\Managers\TelegramChatManager;
use App\Managers\TelegramMessageManager;
use App\Managers\TelegramUserManager;
use App\Models\TelegramChat;
use App\Models\TelegramUser;
use App\Services\RedisService;
use App\Traits\HasTelegramCallback;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Update;

abstract class TelegramCommand extends Command
{
    use HasTelegramCallback;

    /** Нужно ли отображать команду в списке команд */
    public bool $hasParam = false;

    protected ?Chat $chat = null;

    private RedisService $redisService;
    protected ?TelegramUser $telegramUser;
    protected ?TelegramChat $telegramChat = null;
    protected TelegramMessageManager $telegramMessageManager;
    protected ChatInfo $chatInfo;

    private array $responses = [];

    public function __construct()
    {
        $this->redisService = app(RedisService::class);
        $this->telegramMessageManager = app(TelegramMessageManager::class);
    }

    public function make(Api $telegram, Update $update, array $entity)
    {
        $this->telegram = $telegram;
        $this->update = $update;
        $this->entity = $entity;

        return call_user_func_array([$this, 'handle'], array_values($this->getArguments()));
    }

    abstract public function handleCommand();

    public function handleCallback(CallbackQuery $callbackQuery)
    {
        // TODO: переопределить в дочерней команде для обработки ответов от inline-клавиатуры
    }

    public function handle()
    {
        /** Если возвращается пустая коллекция - значит, чата нет и это системное уведомление Telegram */
        if (!$this->getChat()->count()) {
            return;
        }

        /** Создаём объект с информацией о чате (ChatInfo) и логируем пользователя и сообщение */
        $this->setChatInfo();
        $this->logUser();
        $this->logMessage();

        /** Блокировка групп за исключением админской */
        if ($this->checkIsGroup($this->getChat())) {
            return;
        }

        if (!$this->callbackQuery) {
            /** Обработка команды */
            $this->handleCommand();
        } else {
            /** Обработка запроса из inline-keyboard, если страница отличается от предыдущей */
            $identName = $this->chatInfo->lastCommand->command === $this->name;
            $identPage = $this->chatInfo->lastCommand->page === $this->getDataFromCallbackQuery($this->callbackQuery);
            if ($identName && $identPage) {
                return;
            }

            $this->handleCallback($this->callbackQuery);
        }

        /** Логируем ответ в ту же строку, куда записали запрос */
        $this->logResponses($this->responses);
        $this->redisService->setChatInfo($this->chatInfo);
    }

    private function setChatInfo()
    {
        $chatId = $this->getChat()->id;

        $this->chatInfo = $this->redisService->getChatInfo($chatId);

        /** Если вызов в команде, диалог очищается */
        $this->chatInfo->dialog = Dialog::create([]);

        /** Текущее в Redis = предыдущая команда */
        $this->chatInfo->lastCommand = $this->chatInfo->currentCommand;

        $paramKey = array_key_first($this->arguments);
        $param = $this->arguments[$paramKey] ?? $this->chatInfo->lastCommand->param;
        if (!$this->hasParam) {
            $param = null;
        }

        $page = $this->callbackData;

        $this->chatInfo->currentCommand = \App\Entities\Command::create([
            'command' => $this->name,
            'param' => $param,
            'page' => $page,
        ]);
    }

    protected function getChat(): Collection|Chat
    {
        if (!$this->chat) {
            $this->chat = $this->getUpdate()->getChat();
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

    public function logMessage()
    {
        $this->telegramMessageManager->create([
            'command' => $this->chatInfo->currentCommand->toArray(),
        ], $this->telegramUser, $this->telegramChat);
    }

    public function logResponses(array $responses)
    {
        $this->telegramMessageManager->update(['responses' => $responses]);
    }

    protected function addResponse(array $response)
    {
        $this->responses[] = $response;
    }

    public function replyWithMessage(array $use_sendMessage_parameters): mixed
    {
        $this->addResponse($use_sendMessage_parameters);

        try {
            return parent::replyWithMessage($use_sendMessage_parameters);
        } catch (\Exception $exception) {
            $this->logError($use_sendMessage_parameters, $exception);

            throw $exception;
        }
    }

    public function editMessageText(array $params): \Telegram\Bot\Objects\Message|bool
    {
        $this->addResponse($params);

        try {
            return $this->getTelegram()->editMessageText($params);
        } catch (\Exception $exception) {
            $this->logError($params, $exception);
            throw $exception;
        }
    }

    private function checkIsGroup(Chat $chat): bool
    {
        return $chat->type !== 'private'
            && $chat->id !== env('ERROR_CHAT_ID');
    }

    private function logError(array $params, \Exception $exception)
    {
        Log::info(json_encode([
            'request' => $params,
            'answer' => [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
        ], JSON_UNESCAPED_UNICODE));
    }
}
