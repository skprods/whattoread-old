<?php

namespace App\Events;

class BookDeleted extends Event
{
    public int $bookId;

    public function __construct(int $bookId)
    {
        $this->bookId = $bookId;
    }
}
