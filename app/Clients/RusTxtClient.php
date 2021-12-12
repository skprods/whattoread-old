<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class RusTxtClient
{
    private Client $client;
    private string $baseUrl = 'https://rustxt.ru/';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getMorphologyForWord(string $word): string
    {
        $url = $this->baseUrl . "morfologicheskij-razbor-slova/" . $word;
        $request = new Request('GET', $url);

        $response = $this->client->send($request, ['http_errors' => false, 'verify' => false]);

        return $response->getBody()->getContents();
    }
}