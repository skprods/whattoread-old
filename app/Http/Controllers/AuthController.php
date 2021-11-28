<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Managers\UserManager;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private UserManager $userManager;

    public function __construct()
    {
        $this->userManager = app(UserManager::class);
    }

    /**
     * @OA\Post(
     * path="api/v1/login",
     * summary="Авторизация",
     * description="Авторизация по почте и паролю",
     * operationId="authLogin",
     * tags={"Аутентификация"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *       @OA\Property(property="password", type="string", format="password", example="secret"),
     *       @OA\Property(property="remember", type="boolean", example="true", description="Нужно ли запоминать пользователя (влияет на время жизни токена)"),
     *    ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Успешная авторизация",
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer-токен для доступа к приложению",
     *         @OA\Schema( type="string" )
     *     )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Переданные данные невалидны",
     *     @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example="false"),
     *         @OA\Property(
     *             property="error",
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Переданные данные невалидны"),
     *             @OA\Property(
     *                 property="validator",
     *                 type="object",
     *                 @OA\Property(property="email", type="string", example="Неверный email и/или пароль")
     *             )
     *         )
     *     )
     * )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->input('remember');

        $token = $this->userManager->auth($email, $password, $remember);

        return new JsonResponse([], 200, [ 'Authorization' => "Bearer $token" ]);
    }

    /**
     * @OA\Post(
     * path="api/v1/register",
     * summary="Регистрация",
     * description="Регистрация в системе",
     * operationId="authRegister",
     * tags={"Аутентификация"},
     * @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *       required={"first_name","email","password","password_confirmation"},
     *       @OA\Property(property="first_name", type="string", example="Олег"),
     *       @OA\Property(property="email", type="string", format="email", example="user@email.com"),
     *       @OA\Property(property="password", type="string", format="password", example="secret"),
     *       @OA\Property(property="password_confirmation", type="string", format="password", example="secret"),
     *    ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Успешная регистрация и авторизация",
     *     @OA\Header(
     *         header="Authorization",
     *         description="Bearer-токен для доступа к приложению",
     *         @OA\Schema( type="string" )
     *     )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Переданные данные невалидны",
     *     @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example="false"),
     *         @OA\Property(
     *             property="error",
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Переданные данные невалидны"),
     *             @OA\Property(
     *                 property="validator",
     *                 type="object",
     *                 @OA\Property(property="email", type="string", example="Такой email уже существует")
     *             )
     *         )
     *     )
     * )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->userManager->register($request->validated());

        return new JsonResponse([], 201, [ 'Authorization' => "Bearer $token" ]);
    }
}
