<?php

namespace App\Models\BookFrequencies;

use Illuminate\Support\Manager;

class FrequenciesModelManager extends Manager
{
    public const DESCRIPTION_MODEL = 'description';
    public const CONTENT_MODEL = 'content';

    public function model($model = null): Frequency
    {
        return $this->driver($model);
    }

    public function getDefaultDriver(): string
    {
        return self::DESCRIPTION_MODEL;
    }

    public function createDescriptionDriver(): BookDescriptionFrequency
    {
        return app(BookDescriptionFrequency::class);
    }

    public function createContentDriver(): BookContentFrequency
    {
        return app(BookContentFrequency::class);
    }
}