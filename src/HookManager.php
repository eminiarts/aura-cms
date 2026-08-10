<?php

namespace Aura\Base;

class HookManager
{
    protected array $fingerprints = [];

    protected $hooks = [];

    protected array $revisions = [];

    public function addHook($hook, $callback, ?string $fingerprint = null): void
    {
        $this->hooks[$hook][] = $callback;
        $this->fingerprints[$hook][] = $fingerprint ?? $this->callableFingerprint($callback);
        $this->revisions[$hook] = ($this->revisions[$hook] ?? 0) + 1;
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

    public function cacheFingerprint(string $hook): ?string
    {
        $fingerprints = $this->fingerprints[$hook] ?? [];

        if (in_array(null, $fingerprints, true)) {
            return null;
        }

        return hash('sha256', serialize($fingerprints));
    }

    public function revision(string $hook): int
    {
        return $this->revisions[$hook] ?? 0;
    }

    public function version(string $hook): int
    {
        return $this->revision($hook);
    }

    protected function callableFingerprint(mixed $callback): ?string
    {
        if (is_string($callback)) {
            return 'callable:'.$callback;
        }

        if (is_array($callback)
            && count($callback) === 2
            && is_string($callback[0] ?? null)
            && is_string($callback[1] ?? null)) {
            return 'static:'.$callback[0].'::'.$callback[1];
        }

        return null;
    }
}
