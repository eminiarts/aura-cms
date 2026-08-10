<?php

namespace Aura\Base\Traits;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Tags;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HydratesResourceFormFields
{
    /**
     * Hydrate the declared fields that are allowed to enter a resource form.
     *
     * @param  array<string, mixed>  $values
     */
    protected function applyResourceFormFieldValues(array $values): void
    {
        foreach ($this->writableResourceFormFields(FieldValueContext::Create) as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! array_key_exists($slug, $values)) {
                continue;
            }

            $this->form['fields'][$slug] = $values[$slug];
        }
    }

    protected function hydrateResourceFormFields(FieldValueContext $context): void
    {
        $this->form['fields'] ??= [];

        foreach ($this->resourceFormFields($context) as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug)) {
                continue;
            }

            if ($context === FieldValueContext::Create) {
                [$shouldInitialize, $value] = $this->initialResourceFormFieldValue($field);

                if (! $shouldInitialize) {
                    continue;
                }

                $this->form['fields'][$slug] = $this->model->hydrateFieldValueInContext($slug, $value, $context);

                continue;
            }

            $value = $this->model->resolveFieldValueInContext($slug, $context);

            if ($value !== null && method_exists($field['field'], 'hydrate')) {
                $value = $field['field']->hydrate($value, $field);
            }

            $this->form['fields'][$slug] = $value;
        }
    }

    protected function resourceFormFields(FieldValueContext $context): Collection
    {
        $fields = $context === FieldValueContext::Create
            ? $this->model->createFields()
            : $this->model->editFields();

        return collect($this->flattenResourceFormFields($fields))
            ->filter(fn (array $field): bool => ($field['field_type'] ?? null) === 'input')
            ->values();
    }

    /**
     * Remove keys which are not present in the current rendered form before
     * validation or persistence. Field providers may change their output
     * between Livewire requests, so the selection is intentionally resolved
     * at the write boundary rather than trusted from the initial mount.
     */
    protected function sanitizeResourceFormFields(FieldValueContext $context): void
    {
        $values = is_array($this->form['fields'] ?? null) ? $this->form['fields'] : [];
        $sanitized = [];

        foreach ($this->writableResourceFormFields($context) as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! Arr::has($values, $slug)) {
                continue;
            }

            Arr::set($sanitized, $slug, Arr::get($values, $slug));
        }

        $this->form['fields'] = $sanitized;
    }

    protected function writableResourceFormFields(FieldValueContext $context): Collection
    {
        return $this->resourceFormFields($context)
            ->filter(fn (array $field): bool => $this->isResourceFormFieldWritable($field))
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function flattenResourceFormFields(array $fields): array
    {
        $flattened = [];

        foreach ($fields as $field) {
            $flattened[] = $field;

            if (isset($field['fields']) && is_array($field['fields'])) {
                array_push($flattened, ...$this->flattenResourceFormFields($field['fields']));
            }
        }

        return $flattened;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{0: bool, 1: mixed}
     */
    private function initialResourceFormFieldValue(array $field): array
    {
        if (array_key_exists('default', $field)) {
            $value = $field['default'];

            if ($field['field'] instanceof Checkbox
                && isset($field['options'])
                && is_array($field['options'])
                && ! is_array($value)) {
                $value = [$value];
            }

            return [true, $value];
        }

        if ($field['field'] instanceof Boolean) {
            return [true, false];
        }

        if ($field['field'] instanceof Tags) {
            return [true, []];
        }

        return [false, null];
    }

    /**
     * Slug fields stay Livewire-bound while displayed as disabled: their value
     * is derived from their configured source field by the existing Slug view.
     * Other disabled fields are display-only and must not be client-writable.
     *
     * @param  array<string, mixed>  $field
     */
    private function isResourceFormFieldWritable(array $field): bool
    {
        return $field['field'] instanceof Slug
            || ! $field['field']->isDisabled($this->model, $field);
    }
}
