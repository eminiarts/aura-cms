<?php

namespace Aura\Base\Traits;

use Aura\Base\Resource;
use Aura\Base\Table\TableColumnRegistry;

trait InputFieldsTable
{
    public function getColumns()
    {
        return $this->getTableHeaders()->toArray();
    }

    public function getDefaultColumns()
    {
        return $this->getTableHeaders()->map(fn () => true)->toArray();
    }

    public function getTableHeaders()
    {
        $fields = $this->indexHeaderFields()
            ->pluck('name', 'slug');

        // filter out fields that are not on the index or should not be displayed
        $fields = $fields->filter(function ($field, $slug) {
            return $this->isFieldOnIndex($slug) && $this->shouldDisplayField($this->fieldBySlug($slug));
        });

        if (! $this instanceof Resource) {
            return $fields;
        }

        $computed = collect((new TableColumnRegistry)->computed($this))
            ->map(fn ($column): string => $column->label);

        return $fields->merge($computed);
    }

    public function isFieldOnIndex($slug)
    {
        return $this->mappedFieldBySlug($slug)['on_index'] ?? true;
    }
}
