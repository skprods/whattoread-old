<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class UserController
{
    /**
     * @OA\Get(
     * path="api/v1/users",
     * summary="Информация о пользователе",
     * description="Получение информации об авторизованном пользователе",
     * operationId="users.users",
     * tags={"Пользователи"},
     * @OA\Response(
     *     response=200,
     *     description="Успешная выборка",
     *     @OA\JsonContent(ref="#/components/schemas/UserResource")
     * )
     * )
     */
    public function info(): UserResource
    {
        return new UserResource(Auth::user());
    }
}
