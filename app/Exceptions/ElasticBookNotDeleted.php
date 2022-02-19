<?php

namespace App\Exceptions;

use Exception;

class ElasticBookNotDeleted extends Exception
{
    public function __construct(int $bookId)
    {
        parent::__construct("Книга #{$bookId} не удалена из индекса!");
    }
}
