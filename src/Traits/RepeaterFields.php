<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;

trait RepeaterFields
{
    public function addRepeater($slug)
    {
        if (! optional($this->resource['fields'])[$slug]) {
            return $this->resource['fields'][$slug][] = [];
        }

        $last = Arr::last($this->resource['fields'][$slug]);

        $keys = array_keys($last);

        $new = [];

        foreach ($keys as $key) {
            $new[$key] = '';
        }

        $this->resource['fields'][$slug][] = $new;
    }

    public function moveRepeaterDown($slug, $key)
    {
        $array = $this->resource['fields'][$slug];

        if ($key == count($array) - 1) {
            return;
        }

        $item = $array[$key];
        $array[$key] = $array[$key + 1];
        $array[$key + 1] = $item;

        $this->resource['fields'][$slug] = $array;
    }

    public function moveRepeaterUp($slug, $key)
    {
        if ($key == 0) {
            return;
        }

        $array = $this->resource['fields'][$slug];

        $item = $array[$key];
        $array[$key] = $array[$key - 1];
        $array[$key - 1] = $item;

        $this->resource['fields'][$slug] = $array;
    }

    public function removeRepeater($slug, $key)
    {
        unset($this->resource['fields'][$slug][$key]);

        // Reset values
        $this->resource['fields'][$slug] = array_values($this->resource['fields'][$slug]);
    }
}
