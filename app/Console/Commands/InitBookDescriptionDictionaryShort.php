<?php

namespace App\Console\Commands;

use App\Services\BookFrequencyShortService;
use Illuminate\Console\Command;

class InitBookDescriptionDictionaryShort extends Command
{
    protected $signature = 'dictionary:initDescriptionShort {--start=} {--end=} {--chunk=} {--debug}';

    protected $description = 'Создание/обновление быстрого частотного словника по описанию';

    private BookFrequencyShortService $service;

    public function __construct(BookFrequencyShortService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    public function handle()
    {
        $start = $this->option('start');
        $end = $this->option('end');
        $chunk = $this->option('chunk');
        $debug = $this->option('debug');

        $this->service->setDebug($debug);
        $this->service->init($start, $end, $chunk);
    }
}
