<?php

namespace App\Neuronets;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Matrix\Matrix;

class Layer implements Arrayable, Jsonable
{
    /** Позиция слоя в нейросети */
    public int $position;

    /** Коллекция нейронов, входящих в этот слой */
    public Collection $neurons;

    /** Индикатор последнего слоя в нейросети */
    public bool $isLast;

    /** Коэффициент скорости обучения */
    private float $learningCoefficient = 0.0001;

    /** Текущий вектор, для которого вычисляются значения функций активации */
    private array $currentVector;
    /** Вектор из значений функций активации для каждого нейрона (в том же порядке, что и нейроны) */
    private array $activationVector;

    public function __construct(int $position, array $neurons = null, bool $isLast = false)
    {
        $this->position = $position;
        $this->neurons = $neurons ? Neuron::bulkCreate($neurons) : new Collection();
        $this->isLast = $isLast;
    }

    public function getLearningCoefficient(): float
    {
        return $this->learningCoefficient;
    }

    public function setLearningCoefficient(float $learningCoefficient): static
    {
        $this->learningCoefficient = $learningCoefficient;
        return $this;
    }

    /**
     * Запуск слоя на входящем векторе
     *
     * Для вычисления результирующей функции формируется матрица
     * из входящего вектора и матрица из синаптических весов нейронов
     * этого слоя.
     *
     * Матрица синаптических весов формируется следующим образом:
     * в качестве столбцов выступают сами нейроны, а в качестве
     * строк - синаптические веса. Выглядеть эта матрица будет так:
     * -----------------------
     * |  0  |  1  |  2  |  3  | ... | - заголовки столбцов - нейроны (по их ключам)
     * -----------------------
     * | w01 | w11 | w21 | w31 | ... | - первые элементы из массива синапт. весов
     * | w02 | w12 | w22 | w32 | ... | - вторые элементы из массива синапт. весов
     * | w03 | w13 | w23 | w33 | ... | - третьи элементы из массива синапт. весов
     * | w04 | w14 | w24 | w34 | ... | - четвёртые элементы из массива синапт. весов
     * | ... | ... | ... | ... | ... |
     *
     * При перемножении с единичной матрицей вектора получится единичная матрица,
     * размер которой равен количеству нейронов в этом слое, а её значения -
     * результат суммы произведений вершин входящего вектора и вершин вектора
     * синаптических весов.
     *
     * Далее от каждого значения отнимается величина смещения для конкретного нейрона.
     *
     * Метод возвращает вектор (массив) со значениями 1 и 0 - результат выполнения
     * функции активации для каждого из нейронов.
     *
     * @param array $vector - входящий вектор
     * @return array        - результаты фукнции активации для каждого нейрона
     */
    public function run(array $vector): array
    {
        $this->prepareNeurons(count($vector));

        $this->currentVector = $vector;
        $this->activationVector = [];

        /** Преобразование каждого вектора к классу матрицы */
        $originMatrix = new Matrix([$vector]);
        $neuronsMatrix = $this->neuronsMatrix();

        /** Вычисление матрицы - результата перемножения матриц выше */
        $multiply = $originMatrix->multiply($neuronsMatrix)->toArray();
        /**
         * $multiply - единичная матрица, но нам нужен сам вектор,
         * поэтому извлекаем первый (и единственный) элемент
         */
        $multiplyVector = array_shift($multiply);

        /** Заполняем граничный вектор и вектор активаций */
        $targetVector = [];
        $this->neurons->each(function (Neuron $neuron, int $key) use ($multiplyVector, &$targetVector) {
            $targetVector[$key] = $multiplyVector[$key] + $neuron->offset;
        });

        $this->activationVector = $this->calcActivationVector($targetVector);

        /** Возвращаем вектор активаций */
        return $this->activationVector;
    }

    protected function calcActivationVector(array $targetVector): array
    {
        return $this->isLast ? $this->softmax($targetVector) : $this->sigmoid($targetVector);
    }

    private function sigmoid(array $targetVector): array
    {
        foreach ($targetVector as $key => $targetValue) {
            $targetVector[$key] = 1 / (1 + exp(-$targetValue));
        }

        return $targetVector;
    }

    private function softmax(array $vector): array
    {
        $max = max($vector);

        $expSum = 0;
        foreach ($vector as $value) {
            $expSum += exp($value - $max);
        }

        $activation = [];
        foreach ($vector as $key => $value) {
            $activation[$key] = exp($value - $max) / $expSum;
        }

        return $activation;
    }

    /**
     * Коррекция синаптических весов нейронов
     *
     * Метод принимает вектор ожидаемых значений и сравнивает его
     * с вектором активации последнего запуска (результат вызова
     * метода run()). Если ожидаемое значение отличается от фактического,
     * в нейроне изменятся значения синаптических весов в соответствии с
     * дельта-правилом.
     */
    public function correct(array $expected): void
    {
        $this->neurons = $this->neurons->map(function (Neuron $neuron, int $key) use ($expected) {
            $neuron->applyDeltaRule(
                $this->currentVector,
                $this->learningCoefficient,
                $this->activationVector[$key],
                (bool) $expected[$key]
            );

            return $neuron;
        });
    }

    /**
     * Коррекция синаптических весов нейронов
     *
     * Метод принимает матрицу синаптических весов. Она приходит в формате
     * NxM, где N - строки матрицы - синаптические веса, а M - столбцы
     * матрицы - нейроны. Перед корректировкой происходит транспонирование
     * матриц для упрощения обновления синаптических весов.
     */
    public function correctByMatrix(Matrix $weights)
    {
        $weights = $weights->transpose()->toArray();

        $this->neurons->map(function (Neuron $neuron, int $key) use ($weights) {
            $neuron->weights = $weights[$key];
            return $neuron;
        });
    }

    public function neuronsMatrix(): Matrix
    {
        $neuronsCollection = $this->neurons->pluck('weights');
        $weightCount = count($neuronsCollection->first());
        $neuronKeys = $neuronsCollection->keys()->toArray();
        $neuronWeights = $neuronsCollection->toArray();

        $matrix = [];

        for ($i = 0; $i < $weightCount; $i++) {
            $row = [];
            foreach ($neuronKeys as $neuronKey) {
                $row[] = $neuronWeights[$neuronKey][$i];
            }
            $matrix[] = $row;
        }

        return new Matrix($matrix);
    }

    public function clearNeuronsInfo()
    {
        $this->neurons->map(function (Neuron $neuron) {
            $neuron->clearWeights();
            $neuron->clearOffset();
            return $neuron;
        });
    }

    public function toArray(): array
    {
        $neurons = $this->neurons->map(function (Neuron $neuron) {
            return $neuron->toArray();
        })->toArray();

        return [
            'position' => $this->position,
            'neurons' => $neurons,
            'isLast' => $this->isLast,
        ];
    }

    public function prepareNeurons(int $weightsCount)
    {
        $this->neurons->map(function (Neuron $neuron) use ($weightsCount) {
            if (!$neuron->ready($weightsCount)) {
                $neuron->generateOffset();
                $neuron->generateWeights($weightsCount);
            }

            return $neuron;
        });
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public static function create(array $params): self
    {
        return new self($params['position'], $params['neurons'] ?? null, $params['last'] ?? false);
    }

    public static function bulkCreate(array $data): Collection
    {
        $layers = new Collection();

        foreach ($data as $layer) {
            $object = self::create($layer);
            $layers->push($object);
        }

        return $layers;
    }

    public static function generate(int $position, int $neuronsCount, int $weightsCount): self
    {
        $layer = new self($position);

        $neurons = new Collection();
        for ($i = 0; $i < $neuronsCount; $i++) {
            $neurons->push(Neuron::generate($weightsCount));
        }

        $layer->neurons = $neurons;

        return $layer;
    }
}