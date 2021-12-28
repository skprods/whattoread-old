<?php

namespace App\Exceptions;

use App\Entities\Dictionary;
use Exception;

class FileTypeNotAllowedException extends Exception
{
    public function __construct()
    {
        parent::__construct("File type must be one of: " . implode(', ', Dictionary::ALLOWED_EXTENSIONS));
    }
}
