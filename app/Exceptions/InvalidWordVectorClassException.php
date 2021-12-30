<?php

namespace App\Exceptions;

use Exception;

class InvalidWordVectorClassException extends Exception
{
    public function __construct()
    {
        parent::__construct("Свойство wordVectorClass должно быть определено!");
    }
}
