<?php

namespace Eminiarts\Aura\Traits;

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
            'resource.fields' => $this->validationRules(),
        ]);
    }
}
