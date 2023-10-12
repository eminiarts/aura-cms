<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;

trait InputFieldsHelpers
{
    protected static $fieldClassesBySlug = [];

    protected static $fieldsCollectionCache = [];

    protected static $inputFieldSlugs = [];

    public function clearModelCache()
    {
        // set accessibleFieldKeysCache to null
        $this->accessibleFieldKeysCache = null;

        // Generate the cache keys based on the model class
        $fieldsCacheKey = $this->getFieldCacheKey();
        $mappedFieldsCacheKey = $this->getFieldCacheKey().'-mappedFields';

        // Check if the cache keys are bound and remove them
        if (app()->bound($fieldsCacheKey)) {
            app()->offsetUnset($fieldsCacheKey);
        }

        if (app()->bound($mappedFieldsCacheKey)) {
            app()->offsetUnset($mappedFieldsCacheKey);
        }
    }

    public function fieldBySlug($slug)
    {
        return $this->fieldsCollection()->firstWhere('slug', $slug);
    }

    public function fieldClassBySlug($slug)
    {
        $class = get_class($this);

        // Construct a unique key using the class name and the slug
        $key = $class.'-'.$slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldClassesBySlug[$key])) {
            return self::$fieldClassesBySlug[$key];
        }

        // Otherwise, perform the original operation
        $field = $this->fieldBySlug($slug);
        $result = false;

        if (optional($field)['type']) {
            $result = app($field['type']);
        }

        // Store the result in the static array
        self::$fieldClassesBySlug[$key] = $result;

        // Return the result
        return $result;
    }

    public function fieldsCollection()
    {
        // return collect($this->getFields());
        $class = get_class($this);

        if (isset(self::$fieldsCollectionCache[$class])) {
            return self::$fieldsCollectionCache[$class];
        }

        self::$fieldsCollectionCache[$class] = collect($this->getFields());

        return self::$fieldsCollectionCache[$class];
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
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']));
    }

    public function inputFieldsSlugs()
    {
        $class = get_class($this);

        if (isset(self::$inputFieldSlugs[$class])) {
            return self::$inputFieldSlugs[$class];
        }

        self::$inputFieldSlugs[$class] = $this->inputFields()->pluck('slug')->toArray();

        return self::$inputFieldSlugs[$class];
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

        // Bind the mapped fields collection as a singleton if it's not already bound
        // app()->singletonIf($cacheKey, function () {
        //     return $this->fieldsCollection()->map(function ($item) {
        //         $item['field'] = app($item['type'])->field($item);
        //         $item['field_type'] = app($item['type'])->type;

        //         return $item;
        //     });
        // });

        // // Return the cached result
        // return app($cacheKey);

    }

    public function sendThroughPipeline($fields, $pipes)
    {
        // dump('sendThroughPipeline');
        return app(Pipeline::class)
            ->send(clone $fields)
            ->through($pipes)
            ->thenReturn();
    }
}
