<?php

namespace App\Entities;

use Illuminate\Support\Collection;

class Dictionary
{
    /** Словарь в формате слово => количество употреблений */
    private Collection $items;

    public function __construct(Collection $dictionary)
    {
        $this->items = $dictionary;
    }

    public function all(): Collection
    {
        return $this->items;
    }

    /** Общее количество всех слов */
    public function count(): int
    {
        return $this->items->sum();
    }

    /** Количество уникальных слов */
    public function itemsCount(): int
    {
        return $this->items->count();
    }

    public function getVector(): array
    {
        $vector = [];
        $total = $this->count();

        $this->items->each(function ($count, $word) use (&$vector, $total) {
            $vector[] = $count / $total;
        });

        return $vector;
    }
}