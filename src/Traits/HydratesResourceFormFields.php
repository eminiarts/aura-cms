<?php

namespace Aura\Base\Traits;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Fields\Tags;

trait HydratesResourceFormFields
{
    /**
     * Hydrate the declared fields that are allowed to enter a resource form.
     *
     * @param  array<string, mixed>  $values
     */
    protected function applyResourceFormFieldValues(array $values): void
    {
        foreach ($this->model->inputFields() as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! $this->isResourceFormFieldVisible($field) || ! array_key_exists($slug, $values)) {
                continue;
            }

            $this->form['fields'][$slug] = $values[$slug];
        }
    }

    protected function hydrateResourceFormFields(FieldValueContext $context): void
    {
        $this->form['fields'] ??= [];

        foreach ($this->model->inputFields() as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! $this->isResourceFormFieldVisible($field)) {
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

            if (method_exists($field['field'], 'hydrate')) {
                $value = $field['field']->hydrate($value, $field);
            }

            $this->form['fields'][$slug] = $value;
        }
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
     * @param  array<string, mixed>  $field
     */
    private function isResourceFormFieldVisible(array $field): bool
    {
        return ($field['field']->on_forms ?? true) !== false
            && ($field['on_forms'] ?? true) !== false;
    }
}
