<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Eminiarts\Aura\Traits\InputFields;

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
            'post.fields' => $this->validationRules(),
        ]);
    }
}
