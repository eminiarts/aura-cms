<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Illuminate\Pipeline\Pipeline;

trait HasFields
{
    public function fieldsCollection()
    {
        return collect($this->getFields());
    }

    public function getFields()
    {
        return [];
    }

    public function getGroupedFields()
    {
        $fields = $this->mappedFields();

        return $this->sendThroughPipeline($fields, [
            // ApplyGroupedInputs::class, // Enes
            AddIdsToFields::class, // Bajram
            BuildTreeFromFields::class, // Bajram
        ]);
    }

    public function mappedFields()
    {
        return $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
            ->send($fields)
            ->through($pipes)
            ->thenReturn();
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
