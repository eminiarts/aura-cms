<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\Models\Meta;

trait AuraResourceMeta
{
    public static $customTable = false;

    public array $metaFields = [];

    public static bool $usesMeta = true;

    protected $baseFillable = [];

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public function getMetaForeignKey()
    {
        return $this->meta()->getForeignKeyName();
    }

    public function getMetaTable()
    {
        return $this->meta()->getRelated()->getTable();

        // return (new Meta())->getTable();
    }

    /**
     * Determine whether a field is stored in the meta table.
     *
     * Storage combinations:
     * - posts table + meta: base fillable fields stay on posts; input fields outside base fillable use meta.
     * - posts table without meta: no fields use meta.
     * - custom table without meta: input field slugs are custom-table columns.
     * - custom table + meta: base fillable fields are custom-table columns; remaining input fields use meta.
     */
    public function isMetaField($key): bool
    {
        if ($key === 'id') {
            return false;
        }

        // If the model does not use meta, return false
        if (! $this->usesMeta()) {
            return false;
        }

        // If the key is in Base fillable, it is not a meta field
        if (in_array($key, $this->getBaseFillable(), true)) {
            return false;
        }

        // If the key is in the fields, it is a meta field
        if (in_array($key, $this->inputFieldsSlugs(), true)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether a field is stored directly on the model table.
     *
     * For posts-mode resources this means the base posts columns. For custom-table resources without meta,
     * every input field slug is a table column. For custom-table resources with meta, only base fillable
     * fields are table columns and remaining input fields are stored in meta.
     */
    public function isTableField($key): bool
    {
        if (in_array($key, $this->getBaseFillable(), true)) {
            return true;
        }

        if ($this->usesCustomTable() && ! $this->usesMeta()) {
            return in_array($key, $this->inputFieldsSlugs(), true);
        }

        return false;
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        if (! $this->usesMeta()) {
            return;
        }

        return $this->morphMany(Meta::class, 'metable');
    }

    public function saveMetaField(array $metaFields): void
    {
        $this->saveMetaFields($metaFields);
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->metaFields = array_merge($this->metaFields, $metaFields);
    }

    public static function usesCustomTable(): bool
    {
        return static::$customTable;
    }

    public static function usesMeta(): bool
    {
        return static::$usesMeta;
    }
}
