<?php

namespace App\Entities;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JetBrains\PhpStorm\ArrayShape;

class Dialog implements Arrayable, Jsonable
{
    public array $completedSteps = [];
    public array $messages = [];
    public array $search = [];
    public ?int $selectedBookId = null;
    public ?int $bookRating = null;

    public static function create(array $data): Dialog
    {
        $dialog = new self();
        $dialog->completedSteps = $data['completedSteps'] ?? [];
        $dialog->messages = $data['messages'] ?? [];
        $dialog->search = $data['search'] ?? [];
        $dialog->selectedBookId = $data['selectedBookId'] ?? null;
        $dialog->bookRating = $data['bookRating'] ?? null;

        return $dialog;
    }

    #[ArrayShape(['completedSteps' => "array", 'messages' => "array", 'search' => "array", 'selectedBookId' => "int|null", 'bookRating' => "int|null"])]
    public function toArray(): array
    {
        return [
            'completedSteps' => $this->completedSteps,
            'messages' => $this->messages,
            'search' => $this->search,
            'selectedBookId' => $this->selectedBookId,
            'bookRating' => $this->bookRating,
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
