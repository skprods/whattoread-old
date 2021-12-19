<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\SingleResource;

class ThermFrequencyResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'word' => $this->resource->word->word,
            'frequency' => $this->resource->frequency,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
