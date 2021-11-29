<?php

use App\Exceptions\TelegramException;
use App\Services\DialogService;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\Update;

Route::post('/', function () {
    /** @var BotsManager $telegram */
    $telegram = app(BotsManager::class);

    try {
        /** @var Update $update */
        $update = $telegram->commandsHandler(true);

        /** Если входящее сообщение - не команда, инициализируем диалог */
        if (isset($update->message) && $update->message->text[0] !== '/') {
            DialogService::initDialog($telegram, $update);
        }
    } catch (Exception $exception) {
        $e = new TelegramException($exception->getMessage(), $exception->getCode(), $telegram->bot()->getWebhookUpdate());

        $telegram->bot()->sendMessage([
            'chat_id' => env('ERROR_CHAT_ID'),
            'text' => "*ОШИБКА*:\nКод: {$e->getCode()}\n{$e->telegramText}",
            'parse_mode' => 'markdown',
        ]);

        $telegram->bot()->sendMessage([
            'chat_id' => $e->update->getChat()->id,
            'text' => "Что-то пошло не так... Наши администраторы уже в курсе, скоро мы всё исправим.\nПриносим свои извинения. Пожалуйста, попробуйте чуть позже."
        ]);

        return 'ok';
    }

    return 'ok';
});
