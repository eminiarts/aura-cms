<?php

namespace Aura\Base\Traits;

use Illuminate\Support\Arr;

trait FieldsOnComponent
{
    use InputFields;

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }
}
