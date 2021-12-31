<?php

namespace App\Exceptions;

use Exception;

class InvalidVectorClassException extends Exception
{
    public function __construct()
    {
        parent::__construct("Свойство vectorClass должно быть определено!");
    }
}
