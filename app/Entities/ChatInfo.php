<?php

namespace App\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class ChatInfo implements Arrayable, Jsonable
{
    public int $id;
    public LastCommand $lastCommand;
    public Dialog $dialog;

    public function __construct(int $chatId, array $data = [])
    {
        $this->id = $chatId;
        $this->setData($data);
    }

    private function setData(array $data)
    {
        if (isset($data['dialog'])) {
            $this->dialog = Dialog::create($data['dialog']);
        } else {
            $this->dialog = new Dialog();
        }

        if (isset($data['lastCommand'])) {
            $this->lastCommand = LastCommand::create($data['lastCommand']);
        } else {
            $this->lastCommand = new LastCommand();
        }
    }

    public function toArray(): array
    {
        $dialog = isset($this->dialog) ? $this->dialog->toArray() : null;
        $lastCommand = isset($this->lastCommand) ? $this->lastCommand->toArray() : null;

        return [
            'id' => $this->id,
            'lastCommand' => $lastCommand,
            'dialog' => $dialog,
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
