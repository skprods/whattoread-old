<?php

namespace App\Services\Database\WordVectors;

use Illuminate\Support\Manager;

class WordVectorsServiceManager extends Manager
{
    public const DESCRIPTION_SERVICE = 'description';

    public function service($service = null): WordVectorsService
    {
        return $this->driver($service);
    }

    public function getDefaultDriver(): string
    {
        return self::DESCRIPTION_SERVICE;
    }

    public function createDescriptionDriver(): DescriptionWordVectorService
    {
        return app(DescriptionWordVectorService::class);
    }
}