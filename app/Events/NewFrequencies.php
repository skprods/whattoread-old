<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewFrequencies
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $filePath;
    public int $bookId;

    public function __construct(string $filePath, int $bookId)
    {
        $this->filePath = $filePath;
        $this->bookId = $bookId;
    }
}
