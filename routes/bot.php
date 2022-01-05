<?php

use App\Enums\Vectors;
use App\Models\Vectors\BookDescriptionVector;
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

Route::get('test2', function () {
    $book1 = 36111;
    $book2 = 47582;

    $vector1 = BookDescriptionVector::findByBookId($book1);
    $vector2 = BookDescriptionVector::findByBookId($book2);

    $vector1 = $vector1->vector;
    $vector2 = $vector2->vector;

    $scalar = 0;

    foreach ($vector1 as $key => $point) {
        $scalar += $point * $vector2[$key] ?? 0;
    }

    $module1 = 0;
    foreach ($vector1 as $point) {
        $module1 += $point * $point;
    }
    $module1 = sqrt($module1);

    $module2 = 0;
    foreach ($vector2 as $point) {
        $module2 += $point * $point;
    }
    $module2 = sqrt($module2);

    $module = $module1 * $module2;

    $corner = $scalar / $module;
    dd($corner);
});