<?php

namespace App\Managers;

use App\Models\Stat;
use App\Models\User;
use App\Traits\HasUsername;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManager
{
    use HasUsername;

    private ?User $user;
    private StatManager $statManager;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
        $this->statManager = app(StatManager::class);
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

    public function register(array $params)
    {
        $this->create($params);

        $ttl = env('JWT_TTL');
        return auth()->setTTL($ttl)->login($this->user);
    }

    public function create(array $params): User
    {
        if (isset($params['username'])) {
            $params['username'] = $this->prepareUsername($params['username']);
        } else {
            $params['username'] = $this->getUsernameFromEmail($params['email']);
        }

        $params['password'] = Hash::make($params['password']);

        $this->user = app(User::class);
        $this->user->fill($params);
        $this->user->save();

        $this->user = RoleManager::setUserRole($this->user);

        $this->statManager->create(Stat::USER_MODEL, $this->user->id, Stat::CREATED_ACTION);

        return $this->user;
    }
}
