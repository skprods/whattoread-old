<?php

namespace App\Services\Vectors;

use App\Enums\Vectors;
use Illuminate\Support\Manager;

class VectorServiceManager extends Manager
{
    public function entity(string $entity = null): VectorEntityServiceManager
    {
        return $this->driver($entity);
    }

    public function getDefaultDriver(): string
    {
        return Vectors::BOOK_ENTITY;
    }

    public function createBookDriver(): VectorEntityServiceManager
    {
        return app(VectorEntityServiceManager::class, ['entity' => Vectors::BOOK_ENTITY]);
    }

    public function createWordDriver(): VectorEntityServiceManager
    {
        return app(VectorEntityServiceManager::class, ['entity' => Vectors::WORD_ENTITY]);
    }
}