<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;

trait HasFields
{
    public function getFieldsProperty()
    {
        $fields = $this->model->mappedFields();

        // dd($this->model->fieldsForView($fields));

        return $this->model->fieldsForView($fields);
    }

    public function getEditFieldsProperty()
    {
        $fields = $this->model->editFields();

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
