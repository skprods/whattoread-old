<?php

namespace App\Events;

class NewFrequencies extends Event
{
    public string $filePath;
    public int $bookId;

    public function __construct(string $filePath, int $bookId)
    {
        $this->filePath = $filePath;
        $this->bookId = $bookId;
    }
}
