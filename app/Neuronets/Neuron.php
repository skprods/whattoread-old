<?php

namespace App\Neuronets;

use App\Services\VectorService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

class Neuron implements Arrayable, Jsonable
{
    /**
     * Синаптические веса нейрона
     */
    public array $weights;

    /**
     * Смещение для пороговой функции
     *
     * Принимает значение от -1 до 1
     */
    public float|null $offset;

    /**
     * Дополнительная информация о нейроне
     *
     * В неё может входить информация о связанных сущностях БД или
     * любая другая полезная в работе информация.
     */
    public array $data;

    /**
     * @param array $weights        - синаптические веса
     * @param float|null $offset    - смещение для пороговой функции
     * @param array $data           - дополнительная информация о нейроне
     */
    public function __construct(array $weights = [], float|null $offset = null, array $data = [])
    {
        $this->weights = $weights;
        $this->offset = $offset;
        $this->data = $data;
    }

    /** Генерация $count весов */
    public function generateWeights(int $count): self
    {
        $minValue = 1 / VectorService::$accuracy;
        $maxValue = VectorService::$accuracy - 1 / VectorService::$accuracy;

        $this->weights = [];
        for ($i = 1; $i <= $count; $i++) {
            $this->weights[] = round(
                mt_rand($minValue, $maxValue) / VectorService::$accuracy,
                VectorService::$precision
            );
        }

        return $this;
    }

    /** Очистка весов */
    public function clearWeights(): self
    {
        $this->weights = [];
        return $this;
    }

    /** Генерация смещения */
    public function generateOffset(): self
    {
        $minValue = 1 / VectorService::$accuracy;
        $maxValue = VectorService::$accuracy - 1 / VectorService::$accuracy;

        $this->offset = round(mt_rand($minValue, $maxValue) / VectorService::$accuracy, 3);

        return $this;
    }

    /** Очистка смещения */
    public function clearOffset(): self
    {
        $this->offset = null;
        return $this;
    }

    /** Установка дополнительной информации */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function run(float $targetValue): float
    {
        return 1 / (1 + exp(-$targetValue));
    }

    public function ready(int $weightsCount): bool
    {
        return $this->offset !== null && count($this->weights) === $weightsCount;
    }

    public function applyDeltaRule(array $vector, float $learningCoefficient, float $actualValue, bool $activate)
    {
        $expectedValue = $activate ? 1 : 0;

        foreach ($vector as $key => $item) {
            $weight = $this->weights[$key];
            $offset = $learningCoefficient * ($expectedValue - $actualValue) * $item;
            $weight += round($offset, VectorService::$precision);

            $this->weights[$key] = round($weight, VectorService::$precision);
        }
    }

    public function toArray(): array
    {
        return [
            'weights' => $this->weights,
            'offset' => $this->offset,
            'data' => $this->data,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public static function create(array $params): self
    {
        return new self($params['weights'], $params['offset'], $params['data'] ?? []);
    }

    public static function bulkCreate(array $data): Collection
    {
        $neurons = new Collection();

        foreach ($data as $neuron) {
            $neurons->push(self::create($neuron));
        }

        return $neurons;
    }

    public static function generate(int $weightsCount, array $data = []): self
    {
        return (new self())
            ->generateOffset()
            ->generateWeights($weightsCount)
            ->setData($data);
    }
}
