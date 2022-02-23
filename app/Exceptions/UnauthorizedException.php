<?php

namespace App\Exceptions;

/**
 * @OA\Schema(
 *     description="Ошибка авторизации",
 *     @OA\Property( property="success", type="boolean", example="false", description="Успешный ли ответ" ),
 *     @OA\Property(
 *         property="error",
 *         type="object",
 *         @OA\Property( property="code", type="integer", example="401", description="Код ошибки" ),
 *         @OA\Property( property="message", type="string", example="Время действия токена истекло." ),
 *     ),
 * )
 */
class UnauthorizedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('Для совершения этого действия нужно авторизоваться.', 401, 401);
    }
}
