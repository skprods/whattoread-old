<?php

namespace App\Http\Resources\Admin;

use App\Http\Collections\Admin\GenresCollection;
use App\Http\Resources\SingleResource;

/**
 * @OA\Schema(
 *     description="Ресурс книги",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property( property="id", type="integer", example="1", description="Индетификатор книги" ),
 *         @OA\Property( property="title", type="string", example="Капитанская дочка", description="Название книги" ),
 *         @OA\Property( property="author", type="string", example="Алексанлр Пушкин", description="Автор книги" ),
 *         @OA\Property( property="description", type="string", example="Длинное или не очень описание книги",
 *             description="Описание книги",
 *         ),
 *         @OA\Property( property="isbn", type="string", example="978-5-00116-601-6", description="ISBN-код книги" ),
 *         @OA\Property( property="words_count", type="integer", example="104000",
 *             description="Количество слов в книге"
 *         ),
 *         @OA\Property( property="therms_count", type="integer", example="23500", nullable=true,
 *             description="Количество отобранных терминов в словнике",
 *         ),
 *         @OA\Property( property="status", type="string", enum={"moderation", "approved"}, example="moderation",
 *             description="Статус книги",
 *         ),
 *         @OA\Property( property="genres", type="array",
 *             @OA\Items(ref="#/components/schemas/GenreResource/properties/data")
 *         ),
 *         @OA\Property( property="created_at", type="datetime", example="2021-01-01 10:00:00",
 *             description="Дата создания",
 *         ),
 *         @OA\Property( property="updated_at", type="datetime", example="2021-01-01 10:00:00",
 *             description="Дата последнего обновления",
 *         ),
 *     )
 * )
 */
class BookResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'author' => $this->resource->author,
            'status' => $this->resource->status,
            'genres' => new GenresCollection($this->whenLoaded('genres')),
            'words_count' => $this->resource->words_count,
            'therm_frequencies' => $this->resource->therms_count,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
