<?php

namespace App\Providers;

use App\Telegram\BotsManager;
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

        $this->app->bind(BotsManager::class, static function ($app) {
            return (new BotsManager(config('telegram')))->setContainer($app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
