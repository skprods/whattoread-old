<?php

namespace App\Exceptions;

class UnauthorizedException extends BaseException
{
    public function __construct()
    {
        parent::__construct('Для совершения этого действия нужно авторизоваться.', 401, 401);
    }
}
