<?php

namespace App\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManager
{
    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    /**
     * @throws ValidationException
     */
    public function auth($email, $password, $remember = false)
    {
        $this->user = User::findByEmail($email);

        if (!$this->user) {
            throw ValidationException::withMessages([
                "email" => 'Неверный email и/или пароль.',
            ]);
        }

        if (!Hash::check($password, $this->user->password)) {
            throw ValidationException::withMessages([
                "email" => 'Неверный email и/или пароль.',
            ]);
        }

        $ttl = ($remember) ? env('JWT_TTL_REMEMBER') : env('JWT_TTL');

        return auth()->setTTL($ttl)->login($this->user);
    }
}
