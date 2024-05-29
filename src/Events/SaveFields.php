<?php

namespace Aura\Base\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaveFields
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fields;
    public $model;

    /**
     * Create a new event instance.
     *
     * @param array $fields
     * @return void
     */
    public function __construct(array $fields, $model)
    {
        $this->fields = $fields;
        $this->model = $model;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
