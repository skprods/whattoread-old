<?php

namespace App\Support;

use JetBrains\PhpStorm\Pure;

class ElasticSearchDocument
{
    public string $index;
    public string $type;
    public string $id;
    public int $version;
    public int $seqNo;
    public int $primaryTerm;
    public bool $found;
    public array $source;

    #[Pure] public function __construct(array $data)
    {
        $this->index = $data['_index'];
        $this->type = $data['_type'];
        $this->id = $data['_id'];
        $this->version = $data['_version'];
        $this->seqNo = $data['_seq_no'];
        $this->primaryTerm = $data['_primary_term'];
        $this->found = $data['found'];
        $this->source = $data['_source'];
    }
}
