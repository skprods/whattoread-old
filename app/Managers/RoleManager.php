<?php

namespace App\Managers;

use App\Models\User;

class RoleManager
{
    public static function setUserRole(User $user): User
    {
        return $user->assignRole('user');
    }

    public static function setAdminRole(User $user): User
    {
        return $user->assignRole('admin');
    }
}
