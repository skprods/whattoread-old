<?php

namespace App\Services;

use App\Telegram\Dialogs\Dialog;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\Update;

class DialogService
{
    public static function initDialog(BotsManager $telegram, Update $update)
    {
        $chatId = $update->getChat()->id;

        /** @var RedisService $redisService */
        $redisService = app(RedisService::class);
        $chatInfo = $redisService->getChatInfo($chatId);

        $dialogClass = config('telegram.dialogs.' . $chatInfo->lastCommand);

        if ($dialogClass) {
            /** @var Dialog $dialog */
            $dialog = new $dialogClass($telegram, $chatInfo, $update->message);
            $dialog->handle();
        }
    }
}
