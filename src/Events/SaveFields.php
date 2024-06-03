<?php

namespace Aura\Base\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaveFields
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fields;

    public $model;

    public $oldFields;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $fields, $oldFields, $model)
    {
        $this->fields = $fields;
        $this->oldFields = $oldFields;
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
