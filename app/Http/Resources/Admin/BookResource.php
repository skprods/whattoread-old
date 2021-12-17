<?php

namespace App\Http\Resources\Admin;

use App\Http\Collections\Admin\GenresCollection;
use App\Http\Resources\SingleResource;

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
            'therm_frequencies' => $this->resource->thermFrequencies()->count(),
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
