<?php

namespace App\Services;

use App\Support\ElasticQuery;
use App\Support\ElasticSearchResult;
use Elasticsearch\Client;

class ElasticsearchService
{
    public Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createIndex(ElasticQuery $query)
    {
        $params = [
            'index' => $query->key,
            'body' => $query->body,
        ];

        $this->client->indices()->create($params);
    }

    public function deleteIndexIfExists(string $index)
    {
        if ($this->checkIndexExists($index)) {
            $this->client->indices()->delete(['index' => $index]);
        }
    }

    public function createAlias(string $alias, string $index)
    {
        $params['body'] = [
            'actions' => [
                'add' => [
                    'index' => $index,
                    'alias' => $alias
                ]
            ]
        ];

        $this->client->indices()->updateAliases($params);
    }

    public function checkIndexExists(string $index): bool
    {
        return $this->client->indices()->exists(['index' => $index]);
    }

    public function getLastIndexByAlias(string $alias): string|null
    {
        $aliases = $this->client->indices()->getAlias();

        foreach ($aliases as $index => $aliasMapping) {
            if (array_key_exists($alias, $aliasMapping['aliases'])) {
                return $index;
            }
        }

        return null;
    }

    /** Добавление нескольких элементов в индекс */
    public function addData(ElasticQuery $query): bool
    {
        $response = $this->client->bulk([
            'body' => $query->body,
        ]);

        return !$response['errors'];
    }

    public function search(ElasticQuery $query): ElasticSearchResult
    {
        $result = $this->client->search([
            'index' => $query->key,
            'body' => $query->body,
        ]);

        return new ElasticSearchResult($result);
    }

    public function getIndexSize(string $index): int
    {
        $stats = $this->client->indices()->stats();
        $indexStat = $stats['indices'][$index] ?? null;

        if ($indexStat) {
            return $indexStat['primaries']['indexing']['index_total'];
        } else {
            return 0;
        }
    }
}
