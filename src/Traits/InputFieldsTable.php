<?php

namespace Aura\Base\Traits;

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

        return $fields;
    }

    public function isFieldOnIndex($slug)
    {
        return $this->mappedFieldBySlug($slug)['on_index'] ?? true;
    }
}
