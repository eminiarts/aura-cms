<?php

namespace Aura\Base\Traits;

use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
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
        $this->form['fields'] = $this->resolveWritableResourceFormState($context)['values'];
    }

    protected function writableResourceFormFields(FieldValueContext $context): Collection
    {
        return $this->resolveWritableResourceFormState($context)['fields'];
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

        if ($field['field'] instanceof File || $field['field'] instanceof Image) {
            return [true, null];
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

    /**
     * @param  array<string, mixed>  $field
     */
    private function isServerDerivedSlugField(array $field): bool
    {
        return $field['field'] instanceof Slug
            && $field['field']->isDisabled($this->model, $field)
            && ! ($field['custom'] ?? false);
    }

    /**
     * Resolve visibility and protected values as one trust graph. A submitted
     * value only becomes available to later conditions or slug dependencies
     * after its field is currently visible and writable. Protected slugs are
     * never copied from Livewire state; they are derived in dependency order.
     *
     * @return array{fields: Collection<int, array<string, mixed>>, values: array<string, mixed>}
     */
    private function resolveWritableResourceFormState(FieldValueContext $context): array
    {
        $submittedValues = is_array($this->form['fields'] ?? null) ? $this->form['fields'] : [];
        $candidateFields = $this->resourceFormFields($context)
            ->filter(fn (array $field): bool => $this->isResourceFormFieldWritable($field))
            ->values();
        $candidateSlugs = $candidateFields
            ->pluck('slug')
            ->filter(fn (mixed $slug): bool => is_string($slug))
            ->flip();
        $trustedValues = [];
        $visibleSlugs = [];
        $maximumPasses = ($candidateFields->count() * 2) + 1;

        for ($pass = 0; $pass < $maximumPasses; $pass++) {
            $changed = false;

            foreach ($candidateFields as $field) {
                $slug = $field['slug'] ?? null;

                if (! is_string($slug)) {
                    continue;
                }

                if (! isset($visibleSlugs[$slug])) {
                    if (! ConditionalLogic::shouldDisplayFieldForWrite($this->model, $field, $trustedValues)) {
                        continue;
                    }

                    $visibleSlugs[$slug] = true;
                    $changed = true;
                }

                if (Arr::has($trustedValues, $slug)) {
                    continue;
                }

                if (! $this->isServerDerivedSlugField($field)) {
                    if (Arr::has($submittedValues, $slug)) {
                        Arr::set($trustedValues, $slug, Arr::get($submittedValues, $slug));
                        $changed = true;
                    }

                    continue;
                }

                $basedOn = $field['based_on'] ?? null;

                if (! is_string($basedOn)
                    || $basedOn === $slug
                    || ! $candidateSlugs->has($basedOn)
                    || ! isset($visibleSlugs[$basedOn])
                    || ! Arr::has($trustedValues, $basedOn)) {
                    continue;
                }

                Arr::set($trustedValues, $slug, $field['field']->deriveValue(Arr::get($trustedValues, $basedOn)));
                $changed = true;
            }

            if (! $changed) {
                break;
            }
        }

        return [
            'fields' => $candidateFields
                ->filter(fn (array $field): bool => is_string($field['slug'] ?? null) && isset($visibleSlugs[$field['slug']]))
                ->values(),
            'values' => $trustedValues,
        ];
    }
}
