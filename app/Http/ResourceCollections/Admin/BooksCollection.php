<?php

namespace App\Http\ResourceCollections\Admin;

use App\Http\ResourceCollections\CollectionResource;
use App\Http\Resources\Admin\BookResource;

class BooksCollection extends CollectionResource
{
    public $collects = BookResource::class;
}
