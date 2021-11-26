<?php

namespace App\Support;

class ElasticSearchHit
{
    public string $_index;
    public string $_type;
    public string $_id;
    public float $_score;
    public array $_source;

    public function __construct(array $data)
    {
        $this->_index = $data['_index'];
        $this->_type = $data['_type'];
        $this->_id = $data['_id'];
        $this->_score = $data['_score'];
        $this->_source = $data['_source'];
    }
}
