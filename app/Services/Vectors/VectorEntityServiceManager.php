<?php

namespace App\Services\Vectors;

use App\Enums\Vectors;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class VectorEntityServiceManager extends Manager
{
    private string $entity;

    public function __construct(Container $container, string $entity)
    {
        $this->entity = $entity;

        parent::__construct($container);
    }

    public function type(string $type = null): VectorTypeService
    {
        return $this->driver($type);
    }

    public function getDefaultDriver(): string
    {
        return Vectors::DESCRIPTION_TYPE;
    }

    public function createDescriptionDriver(): VectorTypeService
    {
        return new VectorTypeService($this->entity, Vectors::DESCRIPTION_TYPE);
    }

    public function createContentDriver(): VectorTypeService
    {
        return new VectorTypeService($this->entity, Vectors::CONTENT_TYPE);
    }
}
