<?php

namespace App\Listeners;

use App\Events\BookDescriptionUpdated;
use App\Services\BookMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateBookMatches implements ShouldQueue
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
