<?php

namespace App\Managers;

use App\Models\KeyboardParam;

class KeyboardParamManager
{
    public ?KeyboardParam $keyboardParam;

    public function __construct(KeyboardParam $keyboardParam = null)
    {
        $this->keyboardParam = $keyboardParam;
    }

    public function create(array $params): KeyboardParam
    {
        $this->keyboardParam = app(KeyboardParam::class);
        $this->keyboardParam->fill($params);
        $this->keyboardParam->save();

        return $this->keyboardParam;
    }
}
