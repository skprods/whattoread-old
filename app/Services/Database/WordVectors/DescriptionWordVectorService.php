<?php

namespace App\Services\Database\WordVectors;

use App\Models\WordVectors\DescriptionWordVector;

class DescriptionWordVectorService extends WordVectorsService
{
    protected ?string $wordVectorClass = DescriptionWordVector::class;
}