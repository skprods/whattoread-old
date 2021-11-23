<?php

namespace App\Http\Resources\Admin;

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
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
