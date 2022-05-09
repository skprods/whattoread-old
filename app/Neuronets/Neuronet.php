<?php

namespace App\Neuronets;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Matrix\Matrix;

/**
 * @property Collection|Layer[] $layers
 */
abstract class Neuronet implements Arrayable, Jsonable
{
    /** Директория с конфигурационными файлами нейросетей */
    protected string $neuronetsDir;

    /** Название файла с конфигурацией нейросети */
    protected string $filename;

    /** Название нейросети */
    protected string $name;

    /** Количество скрытых слоёв нейросети */
    protected int $hiddenLayersCount = 0;

    /** Число нейронов для скрытых слоёв по умолчанию (см. $hiddenLayersNeuronsCount) */
    protected int $defaultHiddenNeuronsCount = 300;

    /**
     * Количество нейронов в скрытых слоях
     *
     * Если значение $hiddenLayersCount отлично от 0, можно указать
     * количество нейронов на каждый скрытый слой. Например, если
     * указано 5 скрытых слоёв, можно указать 5 целочисленных значений
     * с количеством нейронов. Каждое значение будет учтено при
     * генерации нейронной сети.
     *
     * Если в нейросети указано количество скрытых слоёв, а в массиве
     * не будет значений с количеством нейронов, будет взято значение
     * по умолчанию. Также если значений в массиве меньше, чем число
     * слоёв, с кастомным количеством нейронов будут только первые N
     * слоёв, где N = числу элементов в массиве. У всех последующих
     * слоёв будет $defaultHiddenNeuronsCount нейронов.
     */
    protected array $hiddenLayersNeuronsCount = [];

    public array $layersActivationVectors = [];

    public array $deltas = [];

    public array $derivatives = [];

    /**
     * Вектор предыдущего слоя
     *
     * Используется при проходе по слоям нейросети и получении вектора
     * активации. Нужен для хранения вектора активации с предыдущего
     * слоя для перемножения с матрицей нейронов текущего слоя.
     *
     * Подробнее см. в методе run().
     */
    private array $prevLayerVector;

    /**
     * Слои нейросети, содержащие нейроны и сортирующиеся по полю position
     */
    protected Collection $layers;

    public function __construct(bool $load = true)
    {
        $this->neuronetsDir = $this->neuronetsDir ?? config('neuronets.dir');

        if ($load) {
            $this->load();
        }
    }

    public function getLastLayerPosition(): int
    {
        return $this->layers->keys()->last() + 1;
    }

    /**
     * Обучение нейросети на основе обучающей выборки
     *
     * На вход принимается обучающая выборка - @param Collection $trainData
     * Формат этой выборки настраивается в конкретных реализациях обучения.
     *
     * Также для управления обучением можно указать
     * @param float $learningCoefficient - коэффициент скорости обучения
     * @param bool $fresh                - нужно ли "чистое" обучение (обнуление синаптических весов перед вычислениями)
     *
     * Обучение заключается в проходе по каждому слою нейросети
     * и вычисления корректных значений синаптических весов нейронов
     * каждого слоя.
     */
    abstract public function train(
        Collection $trainData,
        float $learningCoefficient = 0.0001,
        bool $fresh = false
    ): bool;

    /**
     * Генерация финального слоя при генерации нейросети.
     *
     * Результатом выполнения метода должен быть заполненный
     * данными слой. Для генерации слоя и жанров можно
     * использовать статические функции generate().
     */
    abstract protected function generateFinalLayer(): Layer;

    /**
     * Запустить нейронную сеть на входящем векторе
     */
    public function run(array $vector): array
    {
        $this->prevLayerVector = $vector;
        $this->layersActivationVectors = [];

        $this->layers->each(function (Layer $layer) {
            $layerActivationVector = $layer->run($this->prevLayerVector);

            $this->prevLayerVector = $layerActivationVector;
            $this->layersActivationVectors[] = $layerActivationVector;
        });

        /** Последний вектор активации слоя - вектор активации всей нейросети */
        return $this->prevLayerVector;
    }

    /**
     * Загрузка данных нейросети
     *
     * Вся информация о нейросети содержится в json-файле, указанном в
     * свойстве $filename.
     *
     * Если указанного файла не существует или если файл есть, но попытка
     * декодировать JSON не удалась или он пустой, вернётся исключение.
     * В таком случае нужно сгенерировать нейронную сеть с помощью статичного
     * метода generate().
     */
    private function load(): void
    {
        $path = $this->neuronetsDir . $this->filename;
        if (!file_exists($path)) {
            throw new \Exception("Файла нейронной сети не существует: $path");
        }

        $file = file_get_contents($this->neuronetsDir . $this->filename);
        $data = json_decode($file, true);

        if (empty($data)) {
            throw new \Exception("Не удаётся декодировать данные нейронной сети");
        }

        $this->name = $data['name'];
        $this->layers = Layer::bulkCreate($data['layers']);
    }

    /**
     * Генерация нейросети
     *
     * Нейросеть генерируется следующим образом:
     * 1. Создаётся объект нейросети с уже указанным именем
     * 2. Добавляются скрытые слои, если они нужны
     * 3. Добавляется итоговый слой, который будет выдавать ответы
     */
    public static function generate(): self
    {
        $neuronet = new static(false);

        $neuronet->layers = new Collection();
        /** Генерируем скрытые слои */
        for ($position = 1; $position <= $neuronet->hiddenLayersCount; $position++) {
            /** Получаем кастомное или дефолтное количество нейронов для текущего слоя */
            $neuronsCount = $neuronet->hiddenLayersNeuronsCount[$position - 1] ?? $neuronet->defaultHiddenNeuronsCount;
            /** Получаем кастомное или дефолтное количество нейронов для предыдущего слоя */
            $weightsCount = $neuronet->hiddenLayersNeuronsCount[$position - 2] ?? $neuronet->defaultHiddenNeuronsCount;

            $neuronet->layers->push(Layer::generate($position, $neuronsCount, $weightsCount));
        }

        /** Добавляем финальный слой */
        $neuronet->layers->push($neuronet->generateFinalLayer());
        $neuronet->save();

        return $neuronet;
    }

    /**
     * Сохранение нейросети в файл
     *
     * Нейросеть будет сохранена в тот же файл, откуда была загружена.
     */
    public function save()
    {
        $neuronet = $this->toJson(JSON_UNESCAPED_UNICODE);

        file_put_contents($this->neuronetsDir . $this->filename, $neuronet);
    }

    public function toArray(): array
    {
        $layers = $this->layers->map(function (Layer $layer) {
            return $layer->toArray();
        })->toArray();

        return [
            'name' => $this->name,
            'layers' => $layers,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
