<?php

namespace Aura\Base\Traits;

use Aura\Base\Fields\Repeater;
use Illuminate\Support\Arr;

trait InteractsWithFields
{
    /**
     * Columns controlled by Aura's persistence and tenancy pipeline, never by
     * an ordinary resource form payload.
     *
     * @var list<string>
     */
    protected array $protectedFormColumns = [
        'id',
        'team_id',
        'user_id',
        'current_team_id',
        'type',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getCreateFieldsProperty()
    {
        return $this->model->createFields();
    }

    public function getEditFieldsProperty()
    {
        return $this->model->editFields();
    }

    public function getFieldsProperty()
    {
        $fields = $this->model->mappedFields();

        return $this->model->fieldsForView($fields);
    }

    public function getViewFieldsProperty()
    {
        return $this->model->viewFields();
    }

    public function validationAttributes()
    {
        $attributes = [];

        foreach ($this->model->inputFields() as $field) {
            $attributes['form.fields.'.$field['slug']] = __($field['slug']);
        }

        return $attributes;
    }

    /**
     * Pull the virtual global-row intent out of a validated shared-resource
     * form. The caller must route a true value through createGlobal() or
     * promoteToGlobal(); it is never mass assigned to the model.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function pullGlobalFormIntent(array &$attributes): ?bool
    {
        if (! config('aura.teams')
            || ! $this->model::sharesRecordsAcrossTeams()
            || ! array_key_exists('is_global', $attributes)) {
            return null;
        }

        $intent = filter_var($attributes['is_global'], FILTER_VALIDATE_BOOLEAN);
        unset($attributes['is_global']);

        return $intent;
    }

    /**
     * Return only validator-approved fields that are writable on this form
     * path, excluding server-controlled ownership and lifecycle columns.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $formFields
     * @return array<string, mixed>
     */
    protected function validatedFormFields(array $validated, array $formFields): array
    {
        $validatedFields = data_get($validated, 'form.fields', []);

        if (! is_array($validatedFields)) {
            return [];
        }

        $attributes = $this->filterWritableFormValues($validatedFields, $formFields);
        Arr::forget($attributes, $this->protectedFormColumns);

        return $attributes;
    }

    /**
     * Recursively mirror the rendered field tree. Structural wrappers expose
     * their children at the same payload level, while repeater children live
     * inside each submitted row and must be filtered there.
     *
     * @param  array<string|int, mixed>  $values
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function filterWritableFormValues(array $values, array $fields): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $children = isset($field['fields']) && is_array($field['fields'])
                ? $field['fields']
                : [];

            if (($field['field_type'] ?? null) !== 'input') {
                $attributes = array_replace_recursive(
                    $attributes,
                    $this->filterWritableFormValues($values, $children),
                );

                continue;
            }

            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! Arr::has($values, $slug)) {
                continue;
            }

            $value = Arr::get($values, $slug);

            if (($field['field'] ?? null) instanceof Repeater && is_array($value)) {
                $value = collect($value)
                    ->filter(fn (mixed $row): bool => is_array($row))
                    ->map(fn (array $row): array => $this->filterWritableFormValues($row, $children))
                    ->values()
                    ->all();
            }

            Arr::set($attributes, $slug, $value);
        }

        return $attributes;
    }
}
