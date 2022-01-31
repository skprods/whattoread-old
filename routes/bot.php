<?php

use SKprods\Telegram\TelegramBotService;
use Illuminate\Support\Facades\Route;

Route::post('/', function () {
    $service = new TelegramBotService();
    return $service->handle(true);
});
