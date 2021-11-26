<?php

namespace App\Support;

class ElasticQuery
{
    public string $key;
    public array $body;

    /**
     * @param string $key - индекс или алиас, в зависимости от конечного запроса
     * @param array $body - содержимое
     */
    public function __construct(string $key, array $body = [])
    {
        $this->key = $key;
        $this->body = $body;
    }
}
