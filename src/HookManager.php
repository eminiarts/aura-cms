<?php

namespace Aura\Base;

class HookManager
{
    protected $hooks = [];

    public function addHook($hook, $callback)
    {
        $this->hooks[$hook][] = $callback;
    }

    public function applyHooks($hook, $data)
    {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $callback) {
                $data = call_user_func($callback, $data);
            }
        }

        return $data;
    }
}
