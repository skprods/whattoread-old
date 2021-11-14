<?php

use App\Services\DialogService;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Objects\Update;

Route::post('/', function () {
    /** @var BotsManager $telegram */
    $telegram = app(BotsManager::class);

    /** @var Update $update */
    $update = $telegram->commandsHandler(true);

    /** Если входящее сообщение - не команда, инициализируем диалог */
    if ($update->message->text[0] !== '/') {
        DialogService::initDialog($telegram, $update);
    }

    return 'ok';
});
