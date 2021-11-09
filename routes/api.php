<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('test', function () {
    DB::table('books')->orderBy('id')->chunk(100, function (Collection $data) {
        foreach ($data as $book) {
            $category = DB::table('categories')->insertOrIgnore(['name' => $book->category]);
            dump($category);
        }
        dd('gg');
    });
});
