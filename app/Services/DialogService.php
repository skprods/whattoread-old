<?php

namespace App\Services;

use App\Telegram\Dialogs\Dialog;
use App\Telegram\Telegram;
use Telegram\Bot\Objects\Update;

class DialogService
{
    public static function initDialog(Telegram $telegram, Update $update)
    {
        $chatId = $update->getChat()->id;

        /** @var RedisService $redisService */
        $redisService = app(RedisService::class);
        $chatInfo = $redisService->getChatInfo($chatId);

        $dialogClass = config('telegram.dialogs.' . $chatInfo->lastCommand->command);

        if ($dialogClass) {
            /** @var Dialog $dialog */
            $dialog = new $dialogClass($telegram, $chatInfo, $update->message);
            $dialog->handle();
        }
    }
}
