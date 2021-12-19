<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Http\Resources\Admin\ExceptionResource;

class ExceptionsCollection extends CollectionResource
{
    public $collects = ExceptionResource::class;
}
