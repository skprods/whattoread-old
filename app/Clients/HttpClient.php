<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

abstract class HttpClient
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    protected function get(string $url, array $headers = [], array $params = []): ResponseInterface
    {
        if (!empty($params)) {
            $urlParams = [];
            foreach ($params as $key => $value) {
                $urlParams[] = "$key=$value";
            }
            $url .= "?" . implode('&', $urlParams);
        }

        $request = new Request("GET", $url, $headers);

        return $this->client->send($request);
    }

    /**
     * @throws GuzzleException
     */
    protected function post(string $url, array $headers, array $body): ResponseInterface
    {
        $request = new Request("POST", $url, $headers, json_encode($body));
        return $this->client->send($request);
    }
}