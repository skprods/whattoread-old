<?php

use App\Enums\Vectors;
use App\Services\TelegramBotService;
use App\Services\VectorService;
use Illuminate\Support\Facades\Route;

Route::post('/', function () {
    $service = new TelegramBotService();
    return $service->handle();
});

Route::get('test', function () {
    /** @var VectorService $vectorService */
    $vectorService = app(VectorService::class);

    $vectorService->createForBook(10, Vectors::DESCRIPTION_TYPE);
});