<?php

namespace App\Clients;

use GuzzleHttp\Client;

class RusVectoresClient extends HttpClient
{
    private string $host;

    public function __construct(Client $client)
    {
        parent::__construct($client);

        $this->host = config('hosts.rusvectors');
    }

    public function getWordMatches(string $word)
    {
        $url = $this->host . config('variables.dictionaries.rusvectors') . "/$word/api/json";
        $response = $this->get($url);

        return json_decode($response->getBody()->getContents(), true);
    }
}