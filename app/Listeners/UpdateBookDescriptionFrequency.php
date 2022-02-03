<?php

namespace App\Listeners;

use App\Events\BookDescriptionUpdated;
use App\Events\BookFrequencyCreated;
use App\Managers\Dictionaries\FrequencyManager;

class UpdateBookDescriptionFrequency extends Listener
{
    private FrequencyManager $frequencyManager;

    public function __construct()
    {
        $this->frequencyManager = app(FrequencyManager::class);
    }

    public function handle(BookDescriptionUpdated $event)
    {
        $this->frequencyManager->createDescriptionFrequency($event->book);

        BookFrequencyCreated::dispatch($event->book);
    }
}
