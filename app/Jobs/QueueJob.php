<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

abstract class QueueJob extends Job implements ShouldQueue
{
    public $timeout = 7200;
}