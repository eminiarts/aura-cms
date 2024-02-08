<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;

trait RepeaterFields
{
    public function addRepeater($slug)
    {
        if (! optional($this->form['fields'])[$slug]) {
            return $this->form['fields'][$slug][] = [];
        }

        $last = Arr::last($this->form['fields'][$slug]);

        $keys = array_keys($last);

        $new = [];

        foreach ($keys as $key) {
            $new[$key] = '';
        }

        $this->form['fields'][$slug][] = $new;
    }

    public function moveRepeaterDown($slug, $key)
    {
        $array = $this->form['fields'][$slug];

        if ($key == count($array) - 1) {
            return;
        }

        $item = $array[$key];
        $array[$key] = $array[$key + 1];
        $array[$key + 1] = $item;

        $this->form['fields'][$slug] = $array;
    }

    public function moveRepeaterUp($slug, $key)
    {
        if ($key == 0) {
            return;
        }

        $array = $this->form['fields'][$slug];

        $item = $array[$key];
        $array[$key] = $array[$key - 1];
        $array[$key - 1] = $item;

        $this->form['fields'][$slug] = $array;
    }

    public function removeRepeater($slug, $key)
    {
        unset($this->form['fields'][$slug][$key]);

        // Reset values
        $this->form['fields'][$slug] = array_values($this->form['fields'][$slug]);
    }
}
