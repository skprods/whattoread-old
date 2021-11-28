<?php

namespace App\Telegram\Commands;

use App\Entities\ChatInfo;
use App\Managers\TelegramMessageManager;
use App\Managers\TelegramUserManager;
use App\Models\TelegramUser;
use App\Services\RedisService;
use Illuminate\Support\Collection;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Chat;

abstract class TelegramCommand extends Command
{
    private RedisService $redisService;
    protected ?TelegramUser $telegramUser;
    protected ChatInfo $chatInfo;

    private array $responses = [];

    public function __construct()
    {
        $this->redisService = app(RedisService::class);
    }

    abstract public function handleCommand();

    public function handle()
    {
        $this->setChatInfo();
        $this->logUser();

        $this->handleCommand();

        $this->logMessage($this->responses);
        $this->setLastCommand();
    }

    private function setChatInfo()
    {
        $chatId = $this->getChat()->id;

        $this->chatInfo = $this->redisService->getChatInfo($chatId);
    }

    private function setLastCommand()
    {
        $this->chatInfo = new ChatInfo($this->chatInfo->id, $this->name);
        $this->redisService->setChatInfo($this->chatInfo);
    }

    protected function getChat(): Collection|Chat
    {
        return $this->getUpdate()->getChat();
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
            $this->telegramUser = null;
        }
    }

    public function logMessage(array $responses)
    {
        if ($this->telegramUser) {
            app(TelegramMessageManager::class)->create([
                'command' => $this->name,
                'responses' => $responses
            ], $this->telegramUser);
        }
    }

    protected function addResponse(array $response)
    {
        $this->responses[] = $response;
    }

    public function replyWithMessage(array $use_sendMessage_parameters): mixed
    {
        $this->addResponse($use_sendMessage_parameters);
        return parent::replyWithMessage($use_sendMessage_parameters);
    }
}
