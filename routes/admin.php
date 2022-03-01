<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'system'], function () {
    Route::get('info', 'SystemController@info');
    Route::get('exceptions', 'SystemController@exceptions');

    Route::group(['prefix' => 'bot'], function () {
        Route::get('activity', 'SystemController@botActivity');
        Route::get('users', 'SystemController@botUsers');
    });
});

Route::group(['prefix' => 'books'], function () {
    Route::get('/', 'BooksController@index');
    Route::get('{book}', 'BooksController@show');
    Route::post('/', 'BooksController@create');
    Route::put('{book}', 'BooksController@update');
    Route::delete('{book}', 'BooksController@delete');

    Route::post('{book}/frequency', 'BooksController@createFrequency');
    Route::get('{bookRecsShort}/recs', 'RecsController@byBook');
});

Route::group(['prefix' => 'genres'], function () {
    Route::get('/', 'GenresController@index');
});
