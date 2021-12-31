<?php

namespace App\Exceptions;

use Exception;

class InvalidVectorException extends Exception
{
    public function __construct()
    {
        parent::__construct("Свойство vector должно быть определено!");
    }
}
