<?php

namespace Eminiarts\Aura\Aura\Traits;

use Eminiarts\Aura\Pipeline\ApplyGroupedInputs;
use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    public function fieldBySlug($slug)
    {
        return $this->fieldsCollection()->firstWhere('slug', $slug);
    }

    public function fieldClassBySlug($slug)
    {
        if (optional($this->fieldBySlug($slug))['type']) {
            return app($this->fieldBySlug($slug)['type']);
        }

        return false;
    }

    public function fieldsCollection()
    {
        return collect($this->getFields());
    }

    public function findBySlug($array, $slug)
    {
        foreach ($array as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
            if (isset($item['fields'])) {
                $result = $this->findBySlug($item['fields'], $slug);
                if ($result) {
                    return $result;
                }
            }
        }
    }

    public function getFieldSlugs()
    {
        return $this->inputFields()->pluck('slug');
    }

    public function getFieldValue($key)
    {
        return $this->fieldClassBySlug($key)->get($this->fieldBySlug($key), $this->meta->$key);
    }

    public function groupedFieldBySlug($slug)
    {
        $fields = $this->getGroupedFields();

        return $this->findBySlug($fields, $slug);
    }

    public function inputFields()
    {
        // $newFields = $this->sendThroughPipeline($this->newFields, [ApplyGroupedInputs::class]);
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']));
    }

    public function mappedFieldBySlug($slug)
    {
        // dd($this->mappedFields(), $this->newFields);
        return $this->mappedFields()->firstWhere('slug', $slug);
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
}
