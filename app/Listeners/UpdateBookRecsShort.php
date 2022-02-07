<?php

namespace App\Listeners;

use App\Events\BookRecsUpdated;
use App\Services\BookRecsShortService;

class UpdateBookRecsShort extends Listener
{
    private BookRecsShortService $service;

    public function __construct()
    {
        $this->service = app(BookRecsShortService::class);
    }

    public function handle(BookRecsUpdated $event)
    {
        $this->service->createForBook($event->book->id);
    }
}
