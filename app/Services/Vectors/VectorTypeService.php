<?php

namespace App\Services\Vectors;

use App\Enums\Vectors;
use App\Exceptions\InvalidVectorClassException;
use App\Exceptions\InvalidVectorException;
use App\Models\Book;
use App\Models\Vectors\BookContentVector;
use App\Models\Vectors\BookVector;
use App\Models\Vectors\BookDescriptionVector;
use App\Models\Vectors\WordContentVector;
use App\Models\Vectors\WordDescriptionVector;
use App\Models\Word;
use App\Models\Vectors\WordVector;

class VectorTypeService
{
    private string $entity;

    /** Класс модели вектора */
    protected ?string $vectorClass = null;

    /** Модель вектора (указаны родительские классы для $vectorClass) */
    protected WordVector|BookVector|null $vector;

    public function __construct(string $entity, string $type)
    {
        $this->entity = $entity;

        switch ($entity) {
            case Vectors::BOOK_ENTITY:
                $class = $this->getBookModel($type);
                break;
            case Vectors::WORD_ENTITY:
                $class = $this->getWordModel($type);
                break;
            default:
                $class = null;
        }

        $this->vectorClass = $class;
        $this->vector = app($this->vectorClass);
    }

    private function getBookModel(string $type): ?string
    {
        switch ($type) {
            case Vectors::DESCRIPTION_TYPE:
                return BookDescriptionVector::class;
            case Vectors::CONTENT_TYPE:
                return BookContentVector::class;
            default:
                return null;
        }
    }

    private function getWordModel(string $type): ?string
    {
        switch ($type) {
            case Vectors::DESCRIPTION_TYPE:
                return WordDescriptionVector::class;
            case Vectors::CONTENT_TYPE:
                return WordContentVector::class;
            default:
                return null;
        }
    }

    public function setVector(WordVector|BookVector $vector): self
    {
        $this->vector = $vector;

        return $this;
    }

    public function getModel(): WordVector|BookVector|null
    {
        return $this->vector;
    }

    /**
     * @throws InvalidVectorException
     * @throws InvalidVectorClassException
     */
    public function createOrUpdate(array $vector, Word|Book|int $baseItem): WordVector|BookVector
    {
        $baseItemId = is_numeric($baseItem) ? $baseItem : $baseItem->id;

        if ($this->entity === Vectors::WORD_ENTITY) {
            $vectorEntity = app($this->vectorClass)::findByWordId($baseItemId);
        } else {
            $vectorEntity = app($this->vectorClass)::findByBookId($baseItemId);
        }

        if ($vectorEntity) {
            $this->vector = $vectorEntity;
            return $this->update($vector);
        } else {
            return $this->create($vector, $baseItemId);
        }
    }

    /** @throws InvalidVectorClassException */
    public function create(array $vector, Word|Book|int $baseItem): WordVector|BookVector
    {
        if (!$this->vectorClass) {
            throw new InvalidVectorClassException();
        }

        $this->vector = app($this->vectorClass);
        $this->vector->fill([
            'vector' => $vector,
        ]);

        if ($this->entity === Vectors::WORD_ENTITY) {
            $this->vector->word()->associate($baseItem);
        } else {
            $this->vector->book()->associate($baseItem);
        }

        $this->vector->save();

        return $this->vector;
    }

    /** @throws InvalidVectorException */
    public function update(array $vector): WordVector|BookVector
    {
        if (!$this->vector->id) {
            throw new InvalidVectorException();
        }

        $this->vector->fill([
            'vector' => $vector,
        ]);
        $this->vector->save();

        return $this->vector;
    }

    /** @throws InvalidVectorException */
    public function delete(): ?bool
    {
        if (!$this->vector->id) {
            throw new InvalidVectorException();
        }

        return $this->vector->delete();
    }
}