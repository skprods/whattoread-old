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

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->input('remember');

        $token = $this->userManager->auth($email, $password, $remember);

        return new JsonResponse([], 200, [ 'Authorization' => "Bearer $token" ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $token = $this->userManager->register($request->validated());

        return new JsonResponse([], 201, [ 'Authorization' => "Bearer $token" ]);
    }
}
