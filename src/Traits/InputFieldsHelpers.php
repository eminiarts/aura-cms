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


    public function getFieldCacheKey()
    {
        return 'fieldsCollectionCache.' . get_class($this);
    }


    public function fieldsCollection()
    {
        return collect($this->getFields());

        // Generate the cache key based on the model class
        $cacheKey = $this->getFieldCacheKey();

        // Check if the cache already contains the result for this model class
        if (!app()->bound($cacheKey)) {
            // If the cache doesn't contain the result, calculate it and store it in the cache
            $fieldsCollection = collect($this->getFields());
            app()->singleton($cacheKey, function () use ($fieldsCollection) {
                return $fieldsCollection;
            });
        }

        // Return the cached result
        return app($cacheKey);

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

        // Generate the cache key based on the model class and method name
        $cacheKey = $this->getFieldCacheKey() . '-mappedFields';

        // Bind the mapped fields collection as a singleton if it's not already bound
        app()->singletonIf($cacheKey, function () {
            return $this->fieldsCollection()->map(function ($item) {
                $item['field'] = app($item['type'])->field($item);
                $item['field_type'] = app($item['type'])->type;

                return $item;
            });
        });

        // Return the cached result
        return app($cacheKey);

    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
        ->send($fields)
        ->through($pipes)
        ->thenReturn();
    }
}
