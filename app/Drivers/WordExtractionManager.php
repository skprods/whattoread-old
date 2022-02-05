<?php

namespace App\Drivers;

use Illuminate\Support\Manager;
use Illuminate\Contracts\Container\Container;

class WordExtractionManager extends Manager
{
    private string $pathOrString;

    public function __construct(Container $container, string $pathOrString)
    {
        $this->pathOrString = $pathOrString;

        parent::__construct($container);
    }

    public function getDefaultDriver(): string
    {
        return 'text';
    }

    public function createTextDriver(): TextDriver
    {
        return new TextDriver($this->pathOrString);
    }

    public function createFb2Driver(): Fb2Driver
    {
        return new Fb2Driver($this->pathOrString);
    }
}