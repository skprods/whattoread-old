<?php

namespace App\Managers;

use App\Models\Stat;

class StatManager
{
    public function create(string $model, int $modelId, string $action): Stat
    {
        $stat = app(Stat::class);
        $stat->fill([
            'model' => $model,
            'model_id' => $modelId,
            'action' => $action,
        ]);
        $stat->save();

        return $stat;
    }
}
