<?php

namespace App\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class LastCommand implements Arrayable, Jsonable
{
    public ?string $command = null;
    public ?string $param = null;
    public ?string $page = null;

    public static function create(array $data): self
    {
        $lastCommand = new self();
        $lastCommand->command = $data['command'] ?? null;
        $lastCommand->param = $data['param'] ?? null;
        $lastCommand->page = $data['page'] ?? null;

        return $lastCommand;
    }

    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'param' => $this->param,
            'page' => $this->page,
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
