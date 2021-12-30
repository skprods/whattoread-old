<?php

namespace App\Services\Database;

use App\Models\BookFrequencies\FrequenciesModelManager;
use App\Models\WordVectors\WordVector;
use App\Services\Database\WordVectors\WordVectorsServiceManager;

class VectorService
{
    public function createForWord(int $wordId): WordVector
    {
        /** @var FrequenciesModelManager $frequenciesManager */
        $frequenciesManager = app(FrequenciesModelManager::class);
        // TODO: пока хардкод, в будущем нужно будет добавить структуру по аналогии с BookFrequenciesService
        $frequencies = $frequenciesManager->model(FrequenciesModelManager::DESCRIPTION_MODEL)::getByWordId($wordId);

        $vector = $frequencies->pluck('frequency');

        if ($vector->count() < 50) {
            while ($vector->count() < 50) {
                $vector->push(mt_rand() / mt_getrandmax());
            }
        }

        /** @var WordVectorsServiceManager $wordVectorsManager */
        $wordVectorsManager = app(WordVectorsServiceManager::class);
        $wordVectorService = $wordVectorsManager->service(WordVectorsServiceManager::DESCRIPTION_SERVICE);

        return $wordVectorService->createOrUpdate($vector->toArray(), $wordId);
    }
}