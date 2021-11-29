<?php

namespace App\Exceptions;

use Exception;

class UserBookAssociationException extends Exception
{
    public function __construct()
    {
        parent::__construct("Должно быть представлено либо поле user_id, либо telegram_user_id");
    }
}
