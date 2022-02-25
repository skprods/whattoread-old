<?php

namespace App\Providers;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function () {
            return ClientBuilder::create()
                ->setHosts([config('services.search.host')])
                ->build();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $env = $this->app->environment('production')
            || $this->app->environment('testing');

        if ($env) {
            URL::forceScheme('https');
        }
    }
}
