<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::post('/', function () {
    $update = Telegram::commandsHandler(true);
    return 'ok';
});
