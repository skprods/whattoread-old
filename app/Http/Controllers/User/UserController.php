<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @OA\Get(
     * path="api/v1/users",
     * summary="Информация о пользователе",
     * description="Получение информации об авторизованном пользователе",
     * operationId="users.users",
     * tags={"Пользователи"},
     * security={ {"bearer":{}} },
     * @OA\Parameter(
     *     name="Authorization",
     *     in="header",
     *     required=true,
     *     description="Bearer {token}",
     *     @OA\Schema(
     *         type="bearerAuth"
     *     )
     * ),
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
