<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    /** Создание экземпляра модели без сохранения в БД */
    public static function make(array $params): static
    {
        $model = new static();
        $model->fill($params);

        return $model;
    }

    /** Создание экземпляра модели с сохранением в БД */
    public static function create(array $params): static
    {
        $model = static::make($params);
        $model->save();

        return $model;
    }

    /** Создание нескольких моделей с сохранением в БД */
    public static function createMany(array $params): bool
    {
        $models = new Collection();

        foreach ($params as $modelParam) {
            $models->push(static::make($modelParam));
        }

        return static::query()->insert($models->toArray());
    }
}