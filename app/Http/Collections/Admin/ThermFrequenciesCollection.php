<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Models\ThermFrequency;

class ThermFrequenciesCollection extends CollectionResource
{
    public $collects = ThermFrequency::class;
}
