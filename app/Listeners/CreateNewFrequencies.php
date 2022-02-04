<?php

namespace App\Listeners;

use App\Events\NewFrequencies;
use App\Services\FrequencyService;

class CreateNewFrequencies extends Listener
{
    private FrequencyService $manager;

    public $timeout = 7200;

    public function __construct()
    {
        $this->manager = app(FrequencyService::class);
    }

    public function handle(NewFrequencies $event)
    {
        $this->manager->createContentFrequencyFromFile($event->filePath, $event->bookId);
    }
}
