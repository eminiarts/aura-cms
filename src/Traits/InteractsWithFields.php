<?php

namespace Eminiarts\Aura\Aura\Traits;

trait InteractsWithFields
{
    public function getEditFieldsProperty()
    {
        $fields = $this->model->editFields();

        return $this->model->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        $fields = $this->model->mappedFields();

        return $this->model->fieldsForView($fields);
    }

    public function validationAttributes()
    {
        $attributes = [];

        foreach ($this->model->inputFields() as $field) {
            $attributes['post.fields.'.$field['slug']] = $field['slug'];
        }

        return $attributes;
    }
}
