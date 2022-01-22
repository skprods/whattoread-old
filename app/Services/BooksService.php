<?php

namespace App\Services;

use App\Models\Elasticsearch\ElasticBooks;

class BooksService
{
    private ElasticsearchService $elasticsearchService;
    private ElasticBooks $elasticBooks;

    public function findBookInElastic(string $query, int $limit = null, int $offset = 0): array
    {
        $this->elasticsearchService = app(ElasticsearchService::class);
        $this->elasticBooks = app(ElasticBooks::class);

        $modelQuery = $this->elasticBooks->getSearchQuery($query);
        $result = $this->elasticsearchService->search($modelQuery);

        if ($result && !empty($result->hits)) {
            return $limit ? array_slice($result->hits, $offset, $limit) : $result->hits;
        } else {
            return [];
        }
    }
}