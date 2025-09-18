<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ReqPatient implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $account;

    public function __construct($data, $account)
    {
         $this->data = $data;
         $this->account = $account;
    }
    public function broadcastOn()
    {
        return ['request-patient'];
    }
    public function broadcastAs()
    {
        return 'request-patient-event';
    }
}
