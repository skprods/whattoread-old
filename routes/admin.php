<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'system'], function () {
    Route::get('info', 'SystemController@info');

    Route::group(['prefix' => 'bot'], function () {
        Route::get('activity', 'SystemController@botActivity');
        Route::get('users', 'SystemController@botUsers');
    });
});

Route::group(['prefix' => 'books'], function () {
    Route::get('/', 'BooksController@index');
});
