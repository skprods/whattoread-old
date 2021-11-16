<?php

namespace App\Traits;

use Carbon\Carbon;

trait CanPrepareData
{
    protected function prepareDateTime(Carbon $date): string
    {
        return $date->format("Y-m-d H:i:s");
    }

    protected function prepareDate(Carbon $date): string
    {
        return $date->format("Y-m-d");
    }
}
