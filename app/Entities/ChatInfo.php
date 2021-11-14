<?php

namespace App\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class ChatInfo implements Arrayable, Jsonable
{
    public int $id;
    public ?string $lastCommand = null;
    public Dialog $dialog;

    public function __construct(int $chatId, string $lastCommand = null, array $data = [])
    {
        $this->id = $chatId;
        $this->lastCommand = $lastCommand;
        $this->setData($data);
    }

    private function setData(array $data)
    {
        if (isset($data['dialog'])) {
            $this->dialog = Dialog::create($data['dialog']);
        } else {
            $this->dialog = new Dialog();
        }
    }

    #[Pure] #[ArrayShape(['lastCommand' => "string", 'dialog' => "array"])]
    public function toArray(): array
    {
        $dialog = isset($this->dialog) ? $this->dialog->toArray() : null;

        return [
            'lastCommand' => $this->lastCommand,
            'dialog' => $dialog,
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
