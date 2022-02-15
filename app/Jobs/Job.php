<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Facades\Console;

abstract class Job
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected bool $debugMode;
    protected string $jobId;

    public function __construct(bool $debug = false)
    {
        $this->debugMode = $debug;
        $this->jobId = md5(static::class . time());
    }

    protected function log(string $message)
    {
        if ($this->debugMode) {
            Console::info("[{$this->jobId}]: " . $message);
        }

        Log::info("[{$this->jobId}]: " . $message);
    }
}
