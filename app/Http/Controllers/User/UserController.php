<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class UserController
{
    public function info(): UserResource
    {
        return new UserResource(Auth::user());
    }
}
