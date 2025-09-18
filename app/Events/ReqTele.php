<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
class ReqTele implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $id;
    public $from;
    public $to;
    public $patient;
    public $datereq;
    public $title;
    public $facility;
    public function __construct($data)
    {
        $this->id = $data->id;
        $this->from = $data->encoded->lname.', '.$data->encoded->fname.' '.$data->encoded->mname;
        $this->to = $data->doctor_id;
        $this->patient = $data->patient->lname.', '.$data->patient->fname.' '.$data->patient->mname;
        $this->datereq = date('Y-m-d H:i:s', strtotime($data->created_at));
        $this->title = $data->title;
        $this->facility = $data->encoded->facility->facilityname;
    }
    public function broadcastOn()
    {
        return ['request-teleconsult'];
    }
    public function broadcastAs()
    {
        return 'request-teleconsult-event';
    }
}
