<?php

namespace Aura\Base\Traits;

trait WithLivewireHelpers
{
    /**
     * Show a notification.
     *
     * @param  string  $message
     * @param  string  $type
     * @return void
     */
    public function notify($message, $type = 'success')
    {
        $this->dispatch('notify', message: $message, type: $type);
    }
}
