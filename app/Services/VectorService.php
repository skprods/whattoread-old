<?php

namespace App\Services;

use App\Enums\Vectors;
use App\Exceptions\WordFrequenciesNotExistsException;
use App\Models\BookFrequencies\FrequenciesModelManager;
use App\Models\BookFrequencies\Frequency;
use App\Models\Vectors\BookVector;
use App\Models\Vectors\WordVector;
use App\Services\Vectors\VectorServiceManager;

class VectorService
{
    private VectorServiceManager $vectorServiceManager;
    private FrequenciesModelManager $frequenciesModelManager;

    public function __construct()
    {
        $this->vectorServiceManager = app(VectorServiceManager::class);
        $this->frequenciesModelManager = app(FrequenciesModelManager::class);
    }

    /**
     * @param int $wordId   - ID слова в БД
     * @param string $type  - тип вектора (по описанию, по содержимому и т.д., см. Vectors::*_TYPE)
     * @see Vectors
     *
     * @return WordVector
     */
    public function createForWord(int $wordId, string $type): WordVector
    {
        $frequencies = $this->frequenciesModelManager->model($type)::getByWordId($wordId);
        $vector = $frequencies->pluck('frequency');

        if ($vector->count() < 50) {
            while ($vector->count() < 50) {
                $vector->push(mt_rand() / mt_getrandmax());
            }
        }

        $vectorTypeService = $this->vectorServiceManager
            ->entity(Vectors::WORD_ENTITY)
            ->type($type);

        return $vectorTypeService->createOrUpdate($vector->toArray(), $wordId);
    }

    /**
     * @param int $bookId   - ID книги в БД
     * @param string $type  - тип вектора (по описанию, по содержимому и т.д., см. Vectors::*_TYPE)
     * @return BookVector
     */
    public function createForBook(int $bookId, string $type): BookVector
    {
        $frequencies = $this->frequenciesModelManager->model($type)::getByBookId($bookId);
        $wordIds = $frequencies->pluck('word_id')->toArray();

        $wordFrequencies = $frequencies->mapWithKeys(function (Frequency $item) {
            return [$item->word_id => $item->frequency];
        })->toArray();

        if (!count($wordFrequencies)) {
            throw new WordFrequenciesNotExistsException($bookId, $type);
        }

        $bookVector = [];

        $vectorModel = $this->vectorServiceManager
            ->entity(Vectors::WORD_ENTITY)
            ->type($type)
            ->getModel();

        $wordVectors = $vectorModel::getByWordIds($wordIds);

        $wordVectors->each(function (WordVector $wordVector) use (&$bookVector, $wordFrequencies) {
            $vector = $wordVector->vector;
            foreach ($vector as $point => $value) {
                $vector[$point] = $value * $wordFrequencies[$wordVector->word_id];
            }

            // TODO: по-хорошему, у вектора должна быть определенная длина, которая в теории может отличаться от длины вектора слова
            // TODO: надо предусмотреть это
            if (count($bookVector) === 0) {
                $bookVector = $vector;
            } else {
                foreach ($bookVector as $point => $value) {
                    $bookVector[$point] = ($vector[$point] ?? 0) + $value;
                }
            }
        });

        $vectorTypeService = $this->vectorServiceManager
            ->entity(Vectors::BOOK_ENTITY)
            ->type($type);

        return $vectorTypeService->createOrUpdate($bookVector, $bookId);
    }
}