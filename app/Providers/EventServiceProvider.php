<?php

namespace App\Providers;

use App\Events\BookDeleted;
use App\Events\BookUpdated;
use App\Listeners\DeleteElasticBook;
use App\Listeners\UpdateElasticBook;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        BookUpdated::class => [
            UpdateElasticBook::class,
        ],

        BookDeleted::class => [
            DeleteElasticBook::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
