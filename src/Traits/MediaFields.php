<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait MediaFields
{
    public function updateField($data)
    {
        $this->post['fields'][$data['slug']] = $data['value'];
        // $this->save();

        $this->emit('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        // emit update Field
        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    public function removeMediaFromField($slug, $id)
    {
        $field = $this->getField($slug);

        $field = collect($field)->filter(function ($value) use ($id) {
            return $value != $id;
        })->values()->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $field,
        ]);

        // Emit Event selectedMediaUpdated
        $this->emit('selectedMediaUpdated', [
            'slug' => $slug,
            'value' => $field,
        ]);
    }

    public function getField($slug)
    {
        return $this->post['fields'][$slug];
    }
}
