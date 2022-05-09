<?php

namespace App\Neuronets;

abstract class SingleClassifier extends Neuronet
{
    /**
     * Единичный классификатор - это однослойная нейронная сеть.
     * Поэтому обработка для него несколько отличается -
     * задействуется только один слой и результат работы
     * классификатора - результат работы слоя.
     */
    public function run(array $vector): array
    {
        $layer = $this->getLayer();
        return $layer->run($vector);
    }

    public function getLayer(): Layer
    {
        return $this->layers->first();
    }

    public function getNeuron(int $key): Neuron
    {
        return $this->getLayer()->neurons->get($key);
    }
}