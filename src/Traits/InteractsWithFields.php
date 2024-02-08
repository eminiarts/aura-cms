<?php

namespace Eminiarts\Aura\Traits;

trait InteractsWithFields
{
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
            $attributes['form.fields.'.$field['slug']] = $field['slug'];
        }

        return $attributes;
    }
}
