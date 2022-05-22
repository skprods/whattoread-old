<?php

namespace App\Neuronets;

use App\Models\Genre;
use App\Services\VectorService;
use Illuminate\Support\Collection;
use Matrix\Matrix;
use SKprods\LaravelHelpers\Facades\Console;
use Symfony\Component\Console\Helper\ProgressBar;

/** Многослойная нейронная сеть для определения жанра текста */
class GenresNeuronet extends Neuronet
{
    protected string $filename = 'genres.json';
    protected string $name = "Нейросеть для определения жанра по тексту";

    protected int $hiddenLayersCount = 5;

    /** Вектор активации  */
    private array $activationVector;

    private array $expectedVector;

    private ProgressBar $bar;

    /**  */
    public function train(Collection $trainData, float $learningCoefficient = 0.0001, bool $fresh = false): bool
    {
        if (!$trainData->count()) {
            return false;
        }

        $this->bar = Console::bar($trainData->count());
        $this->bar->start();
        $targetFunction = 0;

        $trainData->each(function (array $text) use (&$targetFunction) {
            $this->activationVector = $this->run($text['vector']);

            /** Формирование ожидаемого вектора и подсчёт целевой функции */
            $this->expectedVector = [];
            $this->layers
                ->last()
                ->neurons
                ->each(function (Neuron $neuron, int $key) use ($text, &$targetFunction) {
                    $neuronGenre = $neuron->data['genreId'];
                    $desiredValue = in_array($neuronGenre, $text['genres']) ? 1 : 0;
                    $this->expectedVector[$key] = $desiredValue;

                    /**
                     * Дополняем значение целевой функции произведением ожидаемого
                     * отклика нейрона на логарифм фактического отклика этого нейрона
                     */
                    $targetFunction += $desiredValue * log($this->activationVector[$key]);
                });

            /** Вычисление дельта-векторов и производных */
            $this->calcDeltas($text['vector']);

            /** Обновление синаптических весов с помощью производных */
            $this->updateWeights();

            $this->bar->advance();
        });

        $this->bar->finish();
        Console::info(' Выполнено.');

        Console::info("Обучающая выборка пройдена. Значение целевой функции: " . round(-$targetFunction, 5));

        /** Сохранение весов и остальной информации в файл */
        $this->save();

        return true;
    }

    /**
     * Вычисляем дельты для каждого слоя
     *
     * Сначала вычисляется дельта-вектор для нейронов последнего слоя.
     * Он вычисляется как разница вектора выходных значений нейросети
     * (т.е. последнего слоя) и вектора ожидаемых значений для текущего
     * элемента обучающей выборки.
     *
     * Далее в цикле по всем скрытым слоям:
     * 1. Вычисляем производную от матрицы синаптических весов следующего слоя
     * (т.е. идущего за текущим скрытым). Она вычисляется как произведение
     * транспанированной матрицы выходных значений текущего слоя на дельта-
     * матрицу следующего слоя.
     * Результат этого произведения - вектор, который сохраняется в массив $deltas.
     *
     * 2. Вычисляем дельта-вектор для нейронов текущего слоя. Он вычисляется как
     * произведение Адамара из:
     * а) вектора выходных значений текущего слоя (Yi)
     * б) разницы вектора-строки из единиц и вектора выходных значений текущего слоя
     * Ih - Yi, где h - количество нейронов текущего слоя
     * в) произведения дельта-вектора следующего слоя и транспонированной матрицы
     * синаптических весов следующего слоя (Di+1 * Wi+1)
     *
     * Полученный дельта-вектор сохраняется в массив $deltas с ключом текущего слоя.
     *
     * После прохода по всем скрытым слоям вычисляется производная первого скрытого
     * слоя. Она равна произведению транспонированной матрицы входных значений и
     * дельта-вектора первого скрытого слоя.
     */
    private function calcDeltas(array $incomingVector)
    {
        /** Получаем количество слоёв с векторами активации и обнуляем дельты и производные */
        $lastLayerIndex = count($this->layersActivationVectors) - 1;
        $this->deltas = [];
        $this->derivatives = [];

        /** Вычисляем дельта-вектор для нейронов последнего слоя */
        $nextLayerDelta = VectorService::divide($this->activationVector, $this->expectedVector);
        $this->deltas[$lastLayerIndex] = $nextLayerDelta;

        /** Проходим в цикле по всем скрытым слоям нейросети */
        for ($i = $lastLayerIndex - 1; $i >= 0; $i--) {
            /**
             * Вычисляем производную следующего слоя и сохраняем в $this->derivatives:
             * 1. Получаем дельта-матрицу следующего слоя (которая хранится в $nextLayerDelta)
             * 2. Получаем матрицу активаций нейронов текущего слоя
             * 3. Умножаем транспонированную матрицу активаций нейронов текущего слоя на
             * дельта-матрицу следующего слоя.
             * 4. Сохраняем вектор из полученной матрицы в $this->derivatives для индекса
             * следующего слоя (т.е. i + 1)
             */
            $nextDeltaMatrix = new Matrix([$nextLayerDelta]);
            $currentActivationsMatrix = (new Matrix([$this->layersActivationVectors[$i]]));
            $this->derivatives[$i + 1] = $currentActivationsMatrix->transpose()->multiply($nextDeltaMatrix);

            /**
             * Вычисляем дельта-вектор для нейронов текущего слоя
             *
             * 1. Матрица активаций нейронов текущего слоя уже есть - $currentActivationsVector (I)
             * 2. Получаем единичный вектор с такой же длиной, как вектор активаций текущего слоя
             * 3. Вычисляем разницу единичного вектора и вектора активаций нейронов текущего слоя (II)
             * 4. Вычисляем вектор-произведение дельта-матрицы ($nextDeltaMatrix) и матрицы синаптических
             * весов нейронов следующего слоя. (III)
             * 5. Вычисляем произведение Адамара из (I), (II), (III)
             */
            $currentActivationsVector = $this->layersActivationVectors[$i];
            $singleVector = VectorService::generateUnitsVector(count($currentActivationsVector));
            $divideSingle = VectorService::divide($singleVector, $currentActivationsVector);

            /** @var Layer $nextLayer */
            $nextLayer = $this->layers->get($i + 1);
            $weightsMatrix = $nextLayer->neuronsMatrix();
            $deltaMultiple = $nextDeltaMatrix->multiply($weightsMatrix->transpose())->toArray();
            $deltaMultipleVector = array_shift($deltaMultiple);

            $currentDelta = VectorService::hadamardMultiply(
                $currentActivationsVector,
                $divideSingle,
                $deltaMultipleVector
            );
            /** Сохраняем дельта-вектор текущего слоя */
            $this->deltas[$i] = $currentDelta;
            $nextLayerDelta = $currentDelta;
        }

        /** Вычисляем производную для первого скрытого слоя (его дельта под индексом 0) */
        $incomingMatrix = new Matrix($incomingVector);
        $nextLayerDeltaMatrix = new Matrix([$nextLayerDelta]);
        $firstDerivative = $incomingMatrix->multiply($nextLayerDeltaMatrix);
        $this->derivatives[0] = $firstDerivative;
    }

    /** Обновить веса у нейронов каждого слоя в зависимости от производной слоя */
    private function updateWeights()
    {
        $this->layers->each(function (Layer $layer, int $key) {
            /** @var Matrix $derivative */
            $derivative = $this->derivatives[$key];
            $weightsMatrix = $layer->neuronsMatrix();

            $newWeights = $weightsMatrix->subtract($derivative->multiply($layer->getLearningCoefficient()));
            $layer->correctByMatrix($newWeights);
        });
    }

    protected function generateFinalLayer(): Layer
    {
        $layer = Layer::create(['position' => $this->getLastLayerPosition() + 1]);

        Genre::query()
            ->with(['parents'])
            ->chunk(100, function (\Illuminate\Database\Eloquent\Collection $genres) use ($layer) {
                $genres->each(function (Genre $genre) use ($layer) {
                    /** Нужны только жанры первого уровня - без родительских */
                    if ($genre->parents->isEmpty()) {
                        $layer->neurons->push(Neuron::generate(0, [
                            'genreId' => $genre->id,
                            'genreName' => $genre->name,
                        ]));
                    }
                });
            });

        return $layer;
    }
}
