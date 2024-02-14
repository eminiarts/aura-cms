<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Str;

trait MediaFields
{
    public function getField($slug)
    {
        return $this->form['fields'][$slug];
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
        $this->dispatch('selectedMediaUpdated', [
            'slug' => $slug,
            'value' => $field,
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

    public function updateField($data)
    {
        ray('updateField', $data['value']);
        $this->form['fields'][$data['slug']] = $data['value'];

        $this->dispatch('fieldUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
