<?php

namespace App\Http\Resources;

use App\Traits\CanPrepareData;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     description="Коллекция записей",
 *     @OA\Property( property="success", type="boolean", example="true", description="Успешный ли ответ" ),
 * )
 */
class SingleResource extends JsonResource
{
    use CanPrepareData;

    public $with = [
        'success' => true,
    ];
}
