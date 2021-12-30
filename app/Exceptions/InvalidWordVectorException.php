<?php

namespace App\Exceptions;

use Exception;

class InvalidWordVectorException extends Exception
{
    public function __construct()
    {
        parent::__construct("Свойство wordVector должно быть определено!");
    }
}
