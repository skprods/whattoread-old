<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\SingleResource;

class GenreResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'parent' => new self($this->resource->parent),
            'status' => $this->resource->status,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
