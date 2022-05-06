<?php

namespace App\Listeners;

use App\Events\BookDescriptionFrequencyCreated;
use App\Events\BookGenresUpdated;
use App\Events\BookRecsUpdated;
use App\Services\BookRecsService;

class UpdateBookRecs extends Listener
{
    private BookRecsService $service;

    public function __construct()
    {
        $this->service = app(BookRecsService::class);
    }

    public function handle(BookDescriptionFrequencyCreated|BookGenresUpdated $event)
    {
        $this->service->createForBook($event->book);

        BookRecsUpdated::dispatch($event->book);
    }
}
