<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Models\BookContentFrequency;

class ThermFrequenciesCollection extends CollectionResource
{
    public $collects = BookContentFrequency::class;
}
