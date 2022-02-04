<?php

namespace App\Listeners;

use App\Events\BookDescriptionUpdated;
use App\Events\BookFrequencyCreated;
use App\Services\FrequencyService;

class UpdateBookDescriptionFrequency extends Listener
{
    private FrequencyService $frequencyManager;

    public function __construct()
    {
        $this->frequencyManager = app(FrequencyService::class);
    }

    public function handle(BookDescriptionUpdated $event)
    {
        $this->frequencyManager->createDescriptionFrequency($event->book);

        BookFrequencyCreated::dispatch($event->book);
    }
}
