<?php

namespace App\Events;

use App\Models\Book;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Book $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }
}
