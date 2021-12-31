<?php

namespace App\Listeners;

use App\Events\NewFrequencies;
use App\Services\Frequencies\FrequencyService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateNewFrequencies implements ShouldQueue
{
    private FrequencyService $service;

    public $timeout = 7200;

    public function __construct()
    {
        $this->service = app(FrequencyService::class);
    }

    public function handle(NewFrequencies $event)
    {
        $this->service->createContentFrequencyFromFile($event->filePath, $event->bookId);
    }
}
