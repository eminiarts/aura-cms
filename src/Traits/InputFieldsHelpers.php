<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;
use Eminiarts\Aura\Pipeline\ApplyGroupedInputs;

trait InputFieldsHelpers
{
    protected $fieldsCollectionCache;

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
        // If the cache is not empty, return the cached result
        if ($this->fieldsCollectionCache !== null) {
            return $this->fieldsCollectionCache;
        }

        // If the cache is empty, calculate the result and store it in the cache
        $this->fieldsCollectionCache = collect($this->getFields());

        // Return the newly cached result
        return $this->fieldsCollectionCache;
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
        return $this->fieldsCollection()->pluck('slug');
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
        // ray()->count();
        // ray()->trace();
        // dd('hier');
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
        // Generate a cache key based on the class and method name
        $cacheKey = 'mappedFields-' . get_class($this);

        // Retrieve the cached result if available, otherwise execute the method and store the result in the cache
        return Cache::remember($cacheKey, now()->addMinutes(60), function () {
            return $this->fieldsCollection()->map(function ($item) {
                $item['field'] = app($item['type'])->field($item);
                $item['field_type'] = app($item['type'])->type;

                return $item;
            });
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
