<?php

namespace App\Providers;

use App\Managers\Dictionaries\DictionaryManager;
use Illuminate\Support\ServiceProvider;

class DictionaryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('dictionary', function () {
            return new DictionaryManager();
        });
    }
}
