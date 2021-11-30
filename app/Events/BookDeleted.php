<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookDeleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $bookId;

    public function __construct(int $bookId)
    {
        $this->bookId = $bookId;
    }
}
