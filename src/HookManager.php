<?php

namespace Aura\Base;

class HookManager
{
    protected $hooks = [];

    protected array $versions = [];

    public function addHook($hook, $callback)
    {
        $this->hooks[$hook][] = $callback;
        $this->versions[$hook] = ($this->versions[$hook] ?? 0) + 1;
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

    public function version(string $hook): int
    {
        return $this->versions[$hook] ?? 0;
    }
}
