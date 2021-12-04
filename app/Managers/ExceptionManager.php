<?php

namespace App\Managers;

use App\Models\Exception;

class ExceptionManager
{
    public function create(array $params): Exception
    {
        $exception = app(Exception::class);
        $exception->fill($params);
        $exception->save();

        return $exception;
    }
}
