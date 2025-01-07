<?php

namespace Aura\Base\Collection;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class MetaCollection
 */
class MetaCollection extends Collection
{
    /**
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (in_array($key, static::$proxies)) {
            return parent::__get($key);
        }

        if (isset($this->items) && count($this->items)) {
            $meta = $this->first(function ($meta) use ($key) {
                return $meta->key === $key;
            });

            return $meta ? $meta->value : null;
        }
    }

    /**
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return ! is_null($this->__get($name));
    }
}
