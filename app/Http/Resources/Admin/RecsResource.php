<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\SingleResource;

/**
 * @OA\Schema(
 *     description="Ресурс книги",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property( property="book",  description="Исходная книга",
 *             ref="#/components/schemas/BookResource/properties/data"
 *         ),
 *         @OA\Property( property="matching_book", description="Совпадающая книга",
 *             ref="#/components/schemas/BookResource/properties/data"
 *         ),
 *         @OA\Property( property="author_score", type="integer", example="1",
 *             description="Очки совпадения за автора"
 *         ),
 *         @OA\Property( property="genres_score", type="integer", example="4",
 *             description="Очки совпадения за жанры"
 *         ),
 *         @OA\Property( property="description_score", type="float", example="25.05",
 *             description="Очки совпадения за описание"
 *         ),
 *         @OA\Property( property="total_score", type="integer", example="1",
 *             description="Суммарное число очков"
 *         ),
 *     )
 * )
 */
class RecsResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'book' => new BookResource($this->resource->book),
            'matching_book' => new BookResource($this->resource->matching_book),
            'author_score' => $this->resource->author_score ?? 0,
            'genres_score' => $this->resource->genres_score ?? 0,
            'description_score' => $this->resource->description_score ?? 0,
            'total_score' => $this->resource->total_score ?? 0,
        ];
    }
}
