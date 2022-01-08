<?php

namespace App\Listeners;

use App\Events\BookDescriptionUpdated;
use App\Managers\Dictionaries\FrequencyManager;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateBookDescriptionFrequency implements ShouldQueue
{
    private FrequencyManager $frequencyManager;

    public function __construct()
    {
        $this->frequencyManager = app(FrequencyManager::class);
    }

    public function handle(BookDescriptionUpdated $event)
    {
        $this->frequencyManager->createDescriptionFrequency($event->book);
    }
}
