<?php

namespace App\Listeners;

use App\Events\BookFrequencyCreated;
use App\Events\BookGenresUpdated;
use App\Services\BookRecommendationsService;

class UpdateBookRecommendations extends Listener
{
    private BookRecommendationsService $service;

    public function __construct()
    {
        $this->service = app(BookRecommendationsService::class);
    }

    public function handle(BookFrequencyCreated|BookGenresUpdated $event)
    {
        $this->service->createForBook($event->book);
    }
}
