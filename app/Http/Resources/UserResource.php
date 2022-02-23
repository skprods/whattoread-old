<?php

namespace App\Http\Resources;

/**
 * @OA\Schema(
 *     description="Ресурс пользователя",
 *     @OA\Property(
 *          property="id",
 *          type="integer",
 *          example="1",
 *          description="Индетификатор пользователя"
 *     ),
 *     @OA\Property(
 *          property="username",
 *          type="string",
 *          example="user",
 *          description="Имя пользователя (логин)",
 *     ),
 *     @OA\Property(
 *          property="email",
 *          type="string",
 *          example="user@email.com",
 *          description="Почта пользователя",
 *     ),
 *     @OA\Property(
 *          property="first_name",
 *          type="string",
 *          example="Олег",
 *          description="Имя",
 *     ),
 *     @OA\Property(
 *          property="last_name",
 *          type="string",
 *          example="Васнецов",
 *          description="Фамилия",
 *     ),
 *     @OA\Property(
 *          property="email_verified_at",
 *          type="string",
 *          example="2021-01-01 10:00:00",
 *          description="Дата подтверждения почты (если есть)",
 *     ),
 *     @OA\Property(
 *          property="created_at",
 *          type="string",
 *          example="2021-01-01 10:00:00",
 *          description="Дата регистрации",
 *     ),
 *     @OA\Property(
 *          property="updated_at",
 *          type="string",
 *          example="2021-01-01 10:00:00",
 *          description="Дата последнего обновления",
 *     ),
 * )
 */
class UserResource extends SingleResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'username' => $this->resource->username,
            'email' => $this->resource->email,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'email_verified_at' => $this->resource->email_verified_at,
            'created_at' => $this->prepareDateTime($this->resource->created_at),
            'updated_at' => $this->prepareDateTime($this->resource->updated_at),
        ];
    }
}
