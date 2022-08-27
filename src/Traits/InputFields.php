<?php

namespace Eminiarts\Aura\Traits;

use App\Aura\Pipeline\ApplyGroupedInputs;
use App\Aura\Pipeline\ApplyLayoutFields;
use App\Aura\Pipeline\ApplyTabs;
use Illuminate\Pipeline\Pipeline;

trait InputFields
{
    public function fieldsForView($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            ApplyGroupedInputs::class,
            //ApplyTabs::class,
            ApplyLayoutFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
        ->send($fields)
        ->through($pipes)
        ->thenReturn();
    }

    public function mappedFields()
    {
        return $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });
    }

    public function editFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional($field)['on_forms'] === false) {
                return false;
            }

            return true;
        });
    }

    public function indexFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional($field)['on_index'] === false) {
                return false;
            }

            return true;
        });
    }

    public function fieldsCollection()
    {
        return collect($this->getFields());
    }

    public function getHeaders()
    {
        return $this->inputFields()
        ->pluck('name', 'slug')
        ->prepend('ID', 'id');
    }

    public function getColumns()
    {
        return $this->getHeaders()->toArray();
    }

    public function getDefaultColumns()
    {
        return $this->getHeaders()->map(fn () => '1')->toArray();
    }

    public function inputFields()
    {
        return $this->mappedFields()->filter(fn ($item) => $item['field_type'] == 'input');
    }

    public function validationRules()
    {
        $subFields = [];

        $fields = $this->mappedFields()
        ->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater']))
        ->map(function ($item) use (&$subFields) {
            if ($item['field_type'] == 'repeater') {
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

    public function mapIntoValidationFields($item)
    {
        $map = [
            'validation' => $item['validation'] ?? '',
            'slug' => $item['slug'],
        ];

        if (isset($item['fields'])) {
            $map['*'] = collect($item['fields'])->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })->toArray();
        }

        return $map;
    }

    public function fieldBySlug($slug)
    {
        return $this->fieldsCollection()->firstWhere('slug', $slug);
    }

    public function groupedFieldBySlug($slug)
    {
        $fields = $this->sendThroughPipeline($this->mappedFields(), [ApplyGroupedInputs::class]);

        if ($field = $fields->firstWhere('slug', $slug)) {
            return $field;
        }

        // Test Repeater

        return $fields->pluck('fields')->flatten(1)->firstWhere('slug', $slug); // Test this
    }

    public function fieldClassBySlug($slug)
    {
        if (optional($this->fieldBySlug($slug))['type']) {
            return app($this->fieldBySlug($slug)['type']);
        }

        return false;
    }

    public function getFieldValue($key)
    {
        return $this->fieldClassBySlug($key)->get($this->fieldBySlug($key), $this->meta->$key);
    }

    public function displayFieldValue($key, $value = null)
    {
        // ray($this->fieldBySlug($key));

        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value);
        }

        if ($value === null) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $this->meta->$key);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value);
        }

        return $value;
    }
}
