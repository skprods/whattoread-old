<?php

namespace App\Managers;

use App\Models\Stat;
use App\Models\TelegramChat;

class TelegramChatManager
{
    private ?TelegramChat $telegramChat;
    private StatManager $statManager;

    public function __construct(TelegramChat $telegramChat = null)
    {
        $this->telegramChat = $telegramChat;
        $this->statManager = app(StatManager::class);
    }

    public function createOrUpdate(array $params): TelegramChat
    {
        $this->telegramChat = TelegramChat::getByChatId($params['chat_id']);

        if ($this->telegramChat) {
            return $this->update($params);
        } else {
            return $this->create($params);
        }
    }

    public function create(array $params): TelegramChat
    {
        $this->telegramChat = app(TelegramChat::class);
        $this->telegramChat->fill($params);
        $this->telegramChat->save();

        $this->statManager->create(Stat::TELEGRAM_CHAT_MODEL, $this->telegramChat->id, Stat::CREATED_ACTION);

        return $this->telegramChat;
    }

    public function update(array $params): TelegramChat
    {
        $this->telegramChat->fill($params);
        $this->telegramChat->save();

        return $this->telegramChat;
    }
}
