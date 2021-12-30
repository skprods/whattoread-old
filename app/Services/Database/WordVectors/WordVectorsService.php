<?php

namespace App\Services\Database\WordVectors;

use App\Exceptions\InvalidWordVectorClassException;
use App\Exceptions\InvalidWordVectorException;
use App\Models\Word;
use App\Models\WordVectors\WordVector;

abstract class WordVectorsService
{
    /** Класс модели вектора (определяется в дочерних драйверах) */
    protected ?string $wordVectorClass = null;

    /** Модель вектора */
    protected ?WordVector $wordVector;

    public function setWordVector(WordVector $wordVector): self
    {
        $this->wordVector = $wordVector;

        return $this;
    }

    /**
     * @throws InvalidWordVectorException
     * @throws InvalidWordVectorClassException
     */
    public function createOrUpdate(array $vector, Word|int $word): WordVector
    {
        $wordId = is_numeric($word) ? $word : $word->id;
        $wordVector = app($this->wordVectorClass)::findByWordId($wordId);

        if ($wordVector) {
            $this->wordVector = $wordVector;
            return $this->update($vector);
        } else {
            return $this->create($vector, $wordId);
        }
    }

    /** @throws InvalidWordVectorClassException */
    public function create(array $vector, Word|int $word): WordVector
    {
        if (!$this->wordVectorClass) {
            throw new InvalidWordVectorClassException();
        }

        $this->wordVector = app($this->wordVectorClass);
        $this->wordVector->word()->associate($word);
        $this->wordVector->fill([
            'vector' => $vector,
        ]);

        $this->wordVector->save();

        return $this->wordVector;
    }

    /** @throws InvalidWordVectorException */
    public function update(array $vector): WordVector
    {
        if (!$this->wordVector->id) {
            throw new InvalidWordVectorException();
        }

        $this->wordVector->fill([
            'vector' => $vector,
        ]);
        $this->wordVector->save();

        return $this->wordVector;
    }

    /** @throws InvalidWordVectorException */
    public function delete(): ?bool
    {
        if (!$this->wordVector->id) {
            throw new InvalidWordVectorException();
        }

        return $this->wordVector->delete();
    }
}