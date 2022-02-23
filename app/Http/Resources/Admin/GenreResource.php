<?php

namespace App\Http\Resources\Admin;

use App\Http\Collections\Admin\GenresCollection;
use App\Http\Resources\SingleResource;

/**
 * @OA\Schema(
 *     description="Ресурс жанра",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property( property="id", type="integer", example="1", description="Индетификатор" ),
 *         @OA\Property( property="name", type="string", example="Проза", description="Название" ),
 *         @OA\Property(
 *             property="parent",
 *             type="object",
 *             @OA\Property( property="id", type="integer", example="1", description="Индетификатор" ),
 *             @OA\Property( property="name", type="string", example="Родительский жанр", description="Название" ),
 *             @OA\Property( property="status", type="string", enum={"moderation", "approved"}, example="moderation",
 *                 description="Статус",
 *             ),
 *             @OA\Property( property="created_at", type="datetime", example="2021-01-01 10:00:00",
 *                 description="Дата создания",
 *             ),
 *             @OA\Property( property="updated_at", type="datetime", example="2021-01-01 10:00:00",
 *                 description="Дата последнего обновления",
 *             ),
 *         ),
 *         @OA\Property(
 *             property="child",
 *             type="object",
 *             @OA\Property( property="id", type="integer", example="1", description="Индетификатор" ),
 *             @OA\Property( property="name", type="string", example="Дочерний жанр", description="Название" ),
 *             @OA\Property( property="status", type="string", enum={"moderation", "approved"}, example="moderation",
 *                 description="Статус",
 *             ),
 *             @OA\Property( property="created_at", type="datetime", example="2021-01-01 10:00:00",
 *                 description="Дата создания",
 *             ),
 *             @OA\Property( property="updated_at", type="datetime", example="2021-01-01 10:00:00",
 *                 description="Дата последнего обновления",
 *             ),
 *         ),
 *         @OA\Property( property="status", type="string", enum={"moderation", "approved"}, example="moderation",
 *             description="Статус",
 *         ),
 *         @OA\Property( property="created_at", type="datetime", example="2021-01-01 10:00:00",
 *             description="Дата создания",
 *         ),
 *         @OA\Property( property="updated_at", type="datetime", example="2021-01-01 10:00:00",
 *             description="Дата последнего обновления",
 *         ),
 *     )
 * ),
 * @OA\Example
 */
class GenreResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'parent' => GenresCollection::collection($this->whenLoaded('parent')),
            'child' => GenresCollection::collection($this->whenLoaded('child')),
            'status' => $this->resource->status,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
