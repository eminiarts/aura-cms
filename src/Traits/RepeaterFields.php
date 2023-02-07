<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;

trait RepeaterFields
{
    public function addRepeater($slug)
    {
        if (! optional($this->post['fields'])[$slug]) {
            return $this->post['fields'][$slug][] = [];
        }

        $last = Arr::last($this->post['fields'][$slug]);

        $keys = array_keys($last);

        $new = [];

        foreach ($keys as $key) {
            $new[$key] = '';
        }

        $this->post['fields'][$slug][] = $new;
    }

    public function moveRepeaterDown($slug, $key)
    {
        $array = $this->post['fields'][$slug];

        if ($key == count($array) - 1) {
            return;
        }

        $item = $array[$key];
        $array[$key] = $array[$key + 1];
        $array[$key + 1] = $item;

        $this->post['fields'][$slug] = $array;
    }

    public function moveRepeaterUp($slug, $key)
    {
        if ($key == 0) {
            return;
        }

        $array = $this->post['fields'][$slug];

        $item = $array[$key];
        $array[$key] = $array[$key - 1];
        $array[$key - 1] = $item;

        $this->post['fields'][$slug] = $array;
    }

    public function removeRepeater($slug, $key)
    {
        unset($this->post['fields'][$slug][$key]);

        // Reset values
        $this->post['fields'][$slug] = array_values($this->post['fields'][$slug]);
    }
}
