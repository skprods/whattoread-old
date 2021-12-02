<?php

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Route;

Route::post('/', function () {
    $service = new TelegramBotService();
    return $service->handle();
});
