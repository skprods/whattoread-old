<?php

namespace App\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class ChatInfo implements Arrayable, Jsonable
{
    public int $id;
    public Command $lastCommand;
    public Command $currentCommand;
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
            $this->lastCommand = Command::create($data['lastCommand']);
        } else {
            $this->lastCommand = new Command();
        }

        if (isset($data['currentCommand'])) {
            $this->currentCommand = Command::create($data['currentCommand']);
        } else {
            $this->currentCommand = new Command();
        }
    }

    public function toArray(): array
    {
        $dialog = isset($this->dialog) ? $this->dialog->toArray() : null;
        $currentCommand = isset($this->currentCommand) ? $this->currentCommand->toArray() : null;
        $lastCommand = isset($this->lastCommand) ? $this->lastCommand->toArray() : null;

        return [
            'id' => $this->id,
            'currentCommand' => $currentCommand,
            'lastCommand' => $lastCommand,
            'dialog' => $dialog,
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
