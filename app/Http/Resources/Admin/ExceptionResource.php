<?php

namespace App\Http\Resources\Admin;

use App\Http\Collections\Admin\GenresCollection;
use App\Http\Resources\SingleResource;

class ExceptionResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'message' => $this->resource->message,
            'file' => $this->resource->file,
            'line' => $this->resource->line,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
