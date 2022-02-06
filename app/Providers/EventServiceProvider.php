<?php

namespace App\Providers;

use App\Events\BookDeleted;
use App\Events\BookDescriptionUpdated;
use App\Events\BookFrequencyCreated;
use App\Events\BookGenresUpdated;
use App\Events\BookUpdated;
use App\Events\NewFrequencies;
use App\Listeners\CreateNewFrequencies;
use App\Listeners\DeleteElasticBook;
use App\Listeners\UpdateBookDescriptionFrequency;
use App\Listeners\UpdateBookRecommendations;
use App\Listeners\UpdateElasticBook;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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

        /** Изменилось описание книги */
        BookDescriptionUpdated::class => [
            UpdateBookDescriptionFrequency::class,
        ],

        /** Составлен частотный словник книги */
        BookFrequencyCreated::class => [
            UpdateBookRecommendations::class,
        ],

        /** Обновлены жанры книги */
        BookGenresUpdated::class => [
            UpdateBookRecommendations::class,
        ],

        BookUpdated::class => [
            UpdateElasticBook::class,
        ],

        BookDeleted::class => [
            DeleteElasticBook::class,
        ],

        NewFrequencies::class => [
            CreateNewFrequencies::class,
        ]
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
