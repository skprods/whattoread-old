<?php

namespace App\Listeners;

use App\Events\NewFrequencies;
use App\Managers\Dictionaries\FrequencyManager;

class CreateNewFrequencies extends Listener
{
    private FrequencyManager $manager;

    public $timeout = 7200;

    public function __construct()
    {
        $this->manager = app(FrequencyManager::class);
    }

    public function handle(NewFrequencies $event)
    {
        $this->manager->createContentFrequencyFromFile($event->filePath, $event->bookId);
    }
}
