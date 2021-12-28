<?php

namespace App\Drivers;

use Illuminate\Support\Collection;

class TextDriver extends DictionaryDriver
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;

        parent::__construct();
    }

    public function getDictionary(): Collection
    {
        $dictionary = $this->setWordsFromRow($this->string, []);

        return collect($dictionary)->sortDesc();
    }
}