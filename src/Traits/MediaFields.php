<?php

namespace Aura\Base\Traits;

use Aura\Base\Livewire\MediaFieldAuthorization;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

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

    #[On('updateField')]
    public function updateField(mixed $data): void
    {
        if (! is_array($data) || ! is_string($data['slug'] ?? null) || ! array_key_exists('value', $data)) {
            abort(422, 'The media field is invalid.');
        }

        app(MediaFieldAuthorization::class)->authorizeDeclaredField(
            $this->declaredMediaFields(),
            $data['slug'],
            $data['value'],
        );

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

    /** @return array<int, array<string, mixed>> */
    protected function declaredMediaFields(): array
    {
        if (method_exists($this, 'getFields')) {
            $fields = $this->getFields();

            return is_array($fields) ? $fields : [];
        }

        if (is_object($this->model ?? null) && method_exists($this->model, 'getFields')) {
            $fields = $this->model->getFields();

            return is_array($fields) ? $fields : [];
        }

        return [];
    }

    protected function validateMediaFieldsBeforePersistence(): void
    {
        $values = $this->form['fields'] ?? null;

        if (! is_array($values)) {
            abort(422, 'The media fields are invalid.');
        }

        app(MediaFieldAuthorization::class)->authorizeDeclaredFields(
            $this->declaredMediaFields(),
            $values,
        );
    }
}
