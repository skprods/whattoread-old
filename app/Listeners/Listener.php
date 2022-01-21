<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

abstract class Listener implements ShouldQueue
{
    protected ?string $identifier = null;

    protected function log(string $message)
    {
        $class = explode('\\', static::class);
        $className = array_pop($class);
        $className = $this->identifier ? $className . " #{$this->identifier}" : $className;

        Log::info("[$className] $message");
    }
}