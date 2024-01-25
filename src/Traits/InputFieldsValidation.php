<?php

namespace Eminiarts\Aura\Traits;

trait InputFieldsValidation
{
    public function mapIntoValidationFields($item)
    {
        $map = [
            'validation' => $item['validation'] ?? '',
            'slug' => $item['slug'] ?? '',
        ];

        if (isset($item['fields'])) {
            $map['*'] = collect($item['fields'])->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })->toArray();
        }

        return $map;
    }

    public function postFieldValidationRules()
    {
        return collect($this->validationRules())->mapWithKeys(function ($value, $key) {
            return ['post.fields.'.$key => $value];
        })->toArray();
    }

    public function validationRules()
    {
        $subFields = [];

        $fields = $this->getFieldsBeforeTree()
            ->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']))
            ->map(function ($item) use (&$subFields) {
                if (in_array($item['field_type'], ['repeater', 'group'])) {
                    $subFields[] = $item['slug'];

                    return $this->groupedFieldBySlug($item['slug']);
                }

                return $item;
            })
            ->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })
            ->mapWithKeys(function ($item, $key) use (&$subFields) {
                foreach ($subFields as $exclude) {
                    if (str($key)->startsWith($exclude.'.')) {
                        return [$exclude.'.*.'.$item['slug'] => $item['validation']];
                    }
                }

                return [$item['slug'] => $item['validation']];
            })
            ->toArray();


        return $fields;
    }
}
