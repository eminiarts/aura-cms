<?php

namespace Aura\Base\Traits;

trait WithLivewireHelpers
{
    /**
     * Call a method on the component.
     *
     * @param  string  $method
     * @param  mixed  ...$params
     * @return mixed
     */
    public static function callMethod($method, ...$params)
    {
        return app(static::class)->$method(...$params);
    }

    /**
     * Show a notification.
     *
     * @param  string  $message
     * @param  string  $type
     * @return void
     */
    public function notify($message, $type = 'success')
    {
        $this->dispatchBrowserEvent('notify', [
            'message' => $message,
            'type' => $type,
        ]);
    }
}
