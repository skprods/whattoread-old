<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Managers\UserManager;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->input('remember');

        $token = app(UserManager::class)->auth($email, $password, $remember);

        return new JsonResponse([], 200, [ 'Authorization' => "Bearer $token" ]);
    }
}
