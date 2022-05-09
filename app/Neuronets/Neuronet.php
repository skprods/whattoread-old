<?php

namespace App\Neuronets;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

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

    /** Слои нейросети, содержащие нейроны и сортирующиеся по полю position */
    protected Collection $layers;

    /** Максимальное количество эпох обучения */
    protected int $maxEpochsCount;


    public function __construct()
    {
        $this->load();
    }

    public function getLayers(): Collection
    {
        return $this->layers;
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

    public function run(array $vector): array
    {
        // TODO: Создать обработку для многослойной нейронной сети
    }

    /**
     * Загрузка данных нейросети
     *
     * Вся информация о нейросети содержится в json-файле, указанном в
     * свойстве $filename.
     *
     * Если указанного файла не существует, он будет создан с базовым
     * каркасом нейросети. Если файл есть, но попытка декодировать
     * JSON не удалась или он пустой, данные наполнятся из базового
     * каркаса
     */
    private function load(): void
    {
        $this->neuronetsDir = config('neuronets.dir');
        if (!file_exists($this->neuronetsDir . $this->filename)) {
            $this->createNeuronetFile();
        }

        $file = file_get_contents($this->neuronetsDir . $this->filename);
        $data = json_decode($file, true);

        if (empty($data)) {
            $data = $this->generateNeuronetData();
        }

        $this->name = $data['name'];
        $this->layers = Layer::bulkCreate($data['layers']);
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

    /**
     * Создание файла с каркасом нейросети внутри
     */
    private function createNeuronetFile()
    {
        $data = $this->generateNeuronetData();
        $neuronet = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents($this->neuronetsDir . $this->filename, $neuronet);
    }

    /**
     * Генерация каркаса нейросети
     */
    private function generateNeuronetData(): array
    {
        return [
            'name' => 'Ещё одна нейросеть',
            'layers' => [
                [
                    'position' => 1,
                    'neurons' => [
                        [
                            'weights' => [],
                            'offset' => null,
                            'data' => [],
                        ]
                    ],
                ]
            ],
        ];
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
