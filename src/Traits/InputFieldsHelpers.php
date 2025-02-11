<?php

namespace Aura\Base\Traits;

use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    protected static $fieldClassesBySlug = [];

    protected static $fieldsBySlug = [];

    protected static $fieldsCollectionCache = [];

    protected static $inputFieldSlugs = [];

    protected static $mappedFields = [];

    public function fieldBySlug($slug)
    {

        // Construct a unique key using the class name and the slug
        $key = get_class($this).'-'.$slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldsBySlug[$key])) {
            return self::$fieldsBySlug[$key];
        }

        $result = $this->fieldsCollection()->firstWhere('slug', $slug);

        self::$fieldsBySlug[$key] = $result;

        return $result;
    }

    public function fieldClassBySlug($slug)
    {
        // Construct a unique key using the class name and the slug
        $key = get_class($this).'-'.$slug;

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

    public function indexHeaderFields()
    {
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'index']));
    }

    public function inputFields()
    {
        // dump($this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input'])));
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input']));
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
        return $this->mappedFields()->firstWhere('slug', $slug);
    }

    public function mappedFields()
    {
        // mappedFields
        $class = get_class($this);

        if (isset(self::$mappedFields[$class])) {
            return self::$mappedFields[$class];
        }

        self::$mappedFields[$class] = $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return self::$mappedFields[$class];
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
