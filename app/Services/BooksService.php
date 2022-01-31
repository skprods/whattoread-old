<?php

namespace App\Services;

use App\Models\Elasticsearch\ElasticBooks;

class BooksService
{
    private ElasticsearchService $elasticsearchService;
    private ElasticBooks $elasticBooks;

    public function __construct()
    {
        $this->elasticsearchService = app(ElasticsearchService::class);
        $this->elasticBooks = app(ElasticBooks::class);
    }

    public function findBookInElastic(string $query, int $limit = null, int $offset = 0): array
    {
        $modelQuery = $this->elasticBooks->getSearchQuery($query, $limit, $offset);
        $result = $this->elasticsearchService->search($modelQuery);

        return [
            'total' => $result->totalHits,
            'items' => $result->hits,
        ];
    }
}