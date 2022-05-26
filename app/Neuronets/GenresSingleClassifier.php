<?php

namespace App\Neuronets;

use Illuminate\Support\Collection;
use SKprods\LaravelHelpers\Facades\Console;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/** Однослойная нейронная сеть для определения жанра текста */
class GenresSingleClassifier extends SingleClassifier
{
    protected string $filename = "genres_single.json";

    /**
     * Обучение нейросети на основе обучающей выборки
     *
     * На вход принимается обучающая выборка - коллекция в формате
     * [ [ 'vector' => bookVector, 'genreId' => genreId ], ... ]
     *
     * Также для управления обучением можно указать
     * @param float $learningCoefficient - коэффициент скорости обучения
     * @param bool $fresh                - нужно ли "чистое" обучение (обнуление синаптических весов перед вычислениями)
     *
     * Обучение заключается в вычислении корректных значений синаптических
     * весов нейронов слоя классификатора.
     */
    public function train(Collection $trainData, float $learningCoefficient = 0.0001, bool $fresh = false): bool
    {
        if (!$trainData->count()) {
            return false;
        }

        /** Эта нейросеть с одним слоем, поэтому проходить циклом по слоям не нужно */
        $layer = $this->getLayer();

        /** Если нужна чистая тренировка, очищаем веса и смещения у нейронов слоя */
        if ($fresh) {
            Console::info("Очистка нейронов слоя");
            $layer->clearNeuronsInfo();
        }

        /** Устанавливаем коэффициент скорости обучения */
        $layer->setLearningCoefficient($learningCoefficient);

        $diffToIdeal = 0;
        $total = 0;

        /** Обучение */
        $this->trainEpoch($trainData, $layer, $diffToIdeal, $total);

        $correctPercent = round($diffToIdeal / $total * 100, 2);
        Console::info("Обучение завершено. Расхождения с идеалом: $correctPercent%");

        /** Сохранение весов и остальной информации в файл */
        $this->save();

        return true;
    }

    /**
     * Тренировка нейросети в контексте одной эпохи
     *
     * По каждому элементу из тренировочной выборки @param Collection $trainData
     * прогоняются нейроны текущего слоя @param Layer $layer.
     *
     * Результат прогона - вектор активаций, состоящий из 0 и 1 - результатов
     * выполнения функции активации.
     *
     * Далее вычисляется вектор ожидания - вектор из ожидаемых значений активации,
     * которые соответствуют текущему элементу обучающей выборки. С этим вектором
     * слой корректирует значения синаптических весов для ошибочных нейронов.
     *
     * Далее подсчитываются статистические данные - число корректных подсчётов
     * и общее число подсчётов. На основе них вычислится процент корректных
     * вычислений.
     */
    private function trainEpoch(Collection $trainData, Layer $layer, int &$diffToIdeal, int &$total)
    {
        $bar = Console::bar($trainData->count());
        $bar->start();

        $trainData->each(function (array $book) use ($layer, &$diffToIdeal, &$total, $bar) {
            /** Вектор активации нейронов сети */
            $activationVector = $layer->run($book['vector']);

            /** Формирование ожидаемого вектора */
            $expectedVector = [];
            $layer->neurons->each(function (Neuron $neuron, int $key) use ($book, &$expectedVector) {
                $neuronGenre = $neuron->data['genreId'];
                $expectedVector[$key] = in_array($neuronGenre, $book['genres']) ? 1 : 0;
            });

            /** Корректировка синаптических весов */
            $layer->correct($expectedVector);

            $idealDiff = 0;
            foreach ($activationVector as $key => $value) {
                if ($expectedVector[$key] === 1) {
                    /**
                     * Если ожидаемое значение 1 - разница находится путём вычитания,
                     * потому что $value стремится к этой 1.
                     */
                    $idealDiff += 1 - $value;
                } else {
                    /**
                     * Если же ожидаемое значение 0, то разница - это значение $value,
                     * потому что $value стремится к 0.
                     */
                    $idealDiff += $value;
                }
            }

            $diffToIdeal += $idealDiff;
            $total += count($expectedVector);
            $bar->advance();
        });

        $bar->finish();
        Console::info(' Выполнено.');
    }

    public function getGenresByActivationVector(array $vector): array
    {
        $result = [];

        $this->getLayer()->neurons->each(function (Neuron $neuron, int $key) use (&$result, $vector) {
            $result[$neuron->data['genreId']] = $vector[$key];
        });

        arsort($result);

        return $result;
    }

    protected function generateFinalLayer(): Layer
    {
        // TODO: Implement generateFinalLayer() method.
    }
}
