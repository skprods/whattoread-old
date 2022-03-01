<?php

namespace App\Http\Collections\Admin;

use App\Http\Collections\CollectionResource;
use App\Http\Resources\Admin\RecsResource;

/**
 * @OA\Schema(
 *     description="Коллекция рекомендаций",
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RecsResource/properties/data")),
 * )
 */
class RecsCollection extends CollectionResource
{
    public $collects = RecsResource::class;
}
