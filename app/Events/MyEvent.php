<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MyEvent implements ShouldBroadcast
{
    use SerializesModels;

    public string $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    // channel name
    public function broadcastOn()
    {
        return new Channel('my-channel');
    }

    // event name
    public function broadcastAs()
    {
        return 'my-event';
    }

    // payload
    public function broadcastWith()
    {
        return [
            'message' => $this->message
        ];
    }
}
