<?php

namespace App\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    private string $errorMessage;
    private int $errorCode;

    private int $httpCode;

    public function __construct(string $errorMessage, int $errorCode, int $httpCode)
    {
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->httpCode = $httpCode;

        parent::__construct($this->errorMessage, $httpCode);
    }

    public function render()
    {
        return response([
            'success' => false,
            'error' => [
                'message' => $this->errorMessage,
                'code' => $this->errorCode,
            ],
        ], $this->httpCode);
    }
}
