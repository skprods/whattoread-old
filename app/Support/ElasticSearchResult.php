<?php

namespace App\Support;

use JetBrains\PhpStorm\Pure;

class ElasticSearchResult
{
    public int $took;
    public bool $timedOut;
    public ElasticSearchShard $_shards;

    public int $totalHits;
    public ?float $maxScore;
    /** @var ElasticSearchHit[] */
    public array $hits = [];

    #[Pure] public function __construct(array $data)
    {
        $this->took = $data['took'];
        $this->timedOut = $data['timed_out'];
        $this->_shards = new ElasticSearchShard($data['_shards']);

        $this->totalHits = $data['hits']['total']['value'];
        $this->maxScore = $data['hits']['max_score'];

        foreach ($data['hits']['hits'] as $hit) {
            $this->hits[] = new ElasticSearchHit($hit);
        }
    }
}
