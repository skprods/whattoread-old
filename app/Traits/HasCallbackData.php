<?php

namespace App\Traits;

use JetBrains\PhpStorm\ArrayShape;

trait HasCallbackData
{
    #[ArrayShape(['command' => "string", 'data' => "string", 'update_id' => "string"])]
    public function getCallbackData(string $text): array
    {
        [$commandName, $data, $updateId] = explode('_', $text);

        return [
            'command' => $commandName,
            'data' => $data,
            'update_id' => $updateId,
        ];
    }
}