<?php

namespace App\Exceptions;

use Exception;

class WordFrequenciesNotExistsException extends Exception
{
    public function __construct(int $bookId, string $type)
    {
        parent::__construct("Частотный словник для книги #$bookId с типом '$type' не найден!");
    }
}
