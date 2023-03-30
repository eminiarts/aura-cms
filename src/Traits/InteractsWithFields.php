<?php

namespace Eminiarts\Aura\Traits;

trait InteractsWithFields
{
    public function getCreateFieldsProperty()
    {
        $fields = $this->model->createFields();

        return $this->model->fieldsForView($fields);
    }

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

    public function getViewFieldsProperty()
    {
        return $this->model->viewFields();
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
