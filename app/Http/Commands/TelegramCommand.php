<?php

namespace App\Http\Commands;

use App\Managers\TelegramMessageManager;
use App\Managers\TelegramUserManager;
use App\Models\TelegramUser;
use Illuminate\Support\Collection;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Chat;

abstract class TelegramCommand extends Command
{
    protected ?TelegramUser $telegramUser;

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
                'username' => $chat->username,
            ]);
        } else {
            $this->telegramUser = null;
        }
    }

    public function logMessage(array $responses)
    {
        app(TelegramMessageManager::class)->create([
            'command' => $this->name,
            'responses' => $responses
        ], $this->telegramUser);
    }
}
