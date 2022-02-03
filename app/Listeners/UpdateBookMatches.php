<?php

namespace App\Listeners;

use App\Events\BookFrequencyCreated;
use App\Events\BookGenresUpdated;
use App\Services\BookMatchingService;

class UpdateBookMatches extends Listener
{
    private BookMatchingService $service;

    public function __construct()
    {
        $this->service = app(BookMatchingService::class);
    }

    public function handle(BookFrequencyCreated|BookGenresUpdated $event)
    {
        $this->service->createForBook($event->book);
    }
}
