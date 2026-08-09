<?php

namespace Aura\Base\Traits;

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
     * Return only validator-approved resource fields, excluding columns whose
     * values must come from server-side ownership and lifecycle invariants.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function validatedFormFields(array $validated): array
    {
        return collect(data_get($validated, 'form.fields', []))
            ->except($this->protectedFormColumns)
            ->all();
    }
}
