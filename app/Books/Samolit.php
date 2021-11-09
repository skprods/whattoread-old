<?php

namespace App\Books;

use Illuminate\Contracts\Support\Arrayable;

class Samolit implements Arrayable
{
    public string $title;
    public string $description;
    public string $author;
    public array $genres;

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'author' => $this->author,
            'genres' => $this->genres,
        ];
    }
}
