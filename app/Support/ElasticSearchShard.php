<?php

namespace App\Support;

class ElasticSearchShard
{
    public int $total;
    public int $successful;
    public int $skipped;
    public int $failed;

    public function __construct(array $data)
    {
        $this->total = $data['total'];
        $this->successful = $data['successful'];
        $this->skipped = $data['skipped'];
        $this->failed = $data['failed'];
    }
}
