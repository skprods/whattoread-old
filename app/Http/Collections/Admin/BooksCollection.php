<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Http\Resources\Admin\BookResource;

class BooksCollection extends CollectionResource
{
    public $collects = BookResource::class;
}
