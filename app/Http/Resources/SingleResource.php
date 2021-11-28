<?php

namespace App\Http\Resources;

use App\Traits\CanPrepareData;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleResource extends JsonResource
{
    use CanPrepareData;

    public $with = [
        'success' => true,
    ];
}