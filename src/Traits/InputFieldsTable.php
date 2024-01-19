<?php

namespace Eminiarts\Aura\Traits;

trait InputFieldsTable
{
    public function getColumns()
    {
        return $this->getTableHeaders()->toArray();
    }

    public function getDefaultColumns()
    {
        return $this->getTableHeaders()->map(fn () => '1')->toArray();
    }

    public function getTableHeaders()
    {
        $fields = $this->indexHeaderFields()
            // ->filter(function ($field) {
            //     return $field['field_type'] !== 'repeater';
            // })
            ->pluck('name', 'slug')
            // ->prepend('ID', 'id')
            ;

        // filter out fields that are not on the index
        $fields = $fields->filter(function ($field, $slug) {
            return $this->isFieldOnIndex($slug);
        });

        return $fields;
    }

    public function isFieldOnIndex($slug)
    {
        return $this->mappedFieldBySlug($slug)['on_index'] ?? true;
    }
}
