<?php

namespace App\Listeners;

use App\Events\BookDescriptionUpdated;
use App\Services\BookMatchingService;

class UpdateBookMatches extends Listener
{
    private BookMatchingService $service;

    public function __construct()
    {
        $this->service = app(BookMatchingService::class);
    }

    public function handle(BookDescriptionUpdated $event)
    {
        $this->service->createForBook($event->book);
    }
}
