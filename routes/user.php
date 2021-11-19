<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    Route::get('/', 'UserController@info');
});

Route::group(['prefix' => 'system'], function () {
    Route::get('info', 'SystemController@info');
    Route::get('activity/messages', 'SystemController@telegramMessages');
});
