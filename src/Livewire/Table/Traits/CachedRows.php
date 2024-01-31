<?php

namespace Eminiarts\Aura\Livewire\Table\Traits;

trait CachedRows
{
    /**
     * Use cache flag
     *
     * @var bool
     */
    protected $useCache = false;

    /**
     * Enable the use of cache
     *
     * @return void
     */
    public function useCachedRows()
    {
        $this->useCache = true;
    }

    /**
     * Store result in cache and return result
     *
     * @return mixed
     */
    protected function cache(callable $callback)
    {
        $cacheKey = $this->id;

        if ($this->useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $result = $callback();

        cache()->put($cacheKey, $result);

        return $result;
    }
}
