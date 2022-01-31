<?php

namespace App\Telegram;

use App\Managers\TelegramUserManager;
use App\Models\TelegramUser;
use SKprods\Telegram\Core\FreeHandler;
use SKprods\Telegram\Objects\Chat\ChatMemberUpdated;

class CommonHandler extends FreeHandler
{
    public function handle()
    {
        if (isset($this->update->myChatMember)) {
            $this->handleChatMember($this->update->myChatMember);
        }
    }

    private function handleChatMember(ChatMemberUpdated $myChatMember)
    {
        $telegramBotIdOk = $myChatMember->newChatMember->user->id === config('telegram.bots.whattoread.id');
        $kickedStatusOk = $myChatMember->newChatMember->status === TelegramUser::KICKED_STATUS;

        if ($telegramBotIdOk && $kickedStatusOk) {
            $telegramUser = TelegramUser::findByTelegramId($myChatMember->chat->id);

            app(TelegramUserManager::class, ['telegramUser' => $telegramUser])
                ->update(['status' => TelegramUser::KICKED_STATUS]);
        }
    }
}