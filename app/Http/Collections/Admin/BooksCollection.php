<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Http\Resources\Admin\BookResource;

/**
 * @OA\Schema(
 *     description="Коллекция книг",
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BookResource/properties/data")),
 * )
 */
class BooksCollection extends CollectionResource
{
    public $collects = BookResource::class;
}
