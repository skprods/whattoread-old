<?php

namespace App\Listeners;

use App\Events\NewFrequencies;
use App\Managers\FrequenciesManager;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateNewFrequencies implements ShouldQueue
{
    private FrequenciesManager $manager;

    public $timeout = 3600;

    public function __construct()
    {
        $this->manager = app(FrequenciesManager::class);
    }

    public function handle(NewFrequencies $event)
    {
        $this->manager->createFromFile($event->filePath, $event->bookId);
    }
}
