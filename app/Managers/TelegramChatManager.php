<?php

namespace App\Managers;

use App\Models\TelegramChat;

class TelegramChatManager
{
    private ?TelegramChat $telegramChat;

    public function __construct(TelegramChat $telegramChat = null)
    {
        $this->telegramChat = $telegramChat;
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

        return $this->telegramChat;
    }

    public function update(array $params): TelegramChat
    {
        $this->telegramChat->fill($params);
        $this->telegramChat->save();

        return $this->telegramChat;
    }
}
