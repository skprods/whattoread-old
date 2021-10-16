<?php

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    public function __construct()
    {
        parent::__construct("Вы не можете совершить это действие.", 403, 403);
    }
}
