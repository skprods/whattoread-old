<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Http\Resources\Admin\BookResource;
use App\Http\Resources\Admin\GenreResource;

class GenresCollection extends CollectionResource
{
    public $collects = GenreResource::class;
}
