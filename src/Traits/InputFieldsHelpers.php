<?php

namespace Aura\Base\Traits;

use Aura\Base\FieldProviderRegistry;
use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    protected static $fieldClassesBySlug = [];

    protected static $fieldsBeforeTreeBindings = [];

    protected static $fieldsBySlug = [];

    protected static $fieldsCollectionCache = [];

    protected static $inputFieldSlugs = [];

    protected static $mappedFields = [];

    public function fieldBySlug($slug)
    {

        // Construct a unique key using the class name and the slug
        $key = get_class($this).'-'.$this->fieldDefinitionCacheKey().'-'.$slug;

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
        $key = get_class($this).'-'.$this->fieldDefinitionCacheKey().'-'.$slug;

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
        $class = get_class($this);
        $resolution = app(FieldProviderRegistry::class)->resolve(
            $class,
            fn (): array => $this->getFields(),
        );
        $cacheKey = $class.'-'.$resolution->cacheKey;

        if (isset(self::$fieldsCollectionCache[$cacheKey])) {
            return self::$fieldsCollectionCache[$cacheKey];
        }

        self::$fieldsCollectionCache[$cacheKey] = collect($resolution->fields);

        return self::$fieldsCollectionCache[$cacheKey];
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

    /**
     * Reset all process-static field caches.
     *
     * These caches are keyed by resource class and provider resolution, so they
     * must be flushed whenever a definition changes within the same lifecycle.
     * Long-lived workers also flush them between requests and jobs to prevent
     * process state from leaking into the next context.
     */
    public static function flushFieldCache(): void
    {
        foreach (static::$fieldsBeforeTreeBindings as $binding) {
            app()->offsetUnset($binding);
        }

        if (app()->bound(FieldProviderRegistry::class)) {
            app(FieldProviderRegistry::class)->flushResolved();
        }

        static::$fieldClassesBySlug = [];
        static::$fieldsBySlug = [];
        static::$fieldsBeforeTreeBindings = [];
        static::$fieldsCollectionCache = [];
        static::$inputFieldSlugs = [];
        static::$mappedFields = [];
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
        $class = get_class($this).'-'.$this->fieldDefinitionCacheKey();

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
        $class = get_class($this).'-'.$this->fieldDefinitionCacheKey();

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

    protected function fieldDefinitionCacheKey(): string
    {
        return app(FieldProviderRegistry::class)->resolve(
            get_class($this),
            fn (): array => $this->getFields(),
        )->cacheKey;
    }
}
