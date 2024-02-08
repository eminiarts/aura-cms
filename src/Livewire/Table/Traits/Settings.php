<?php

namespace Aura\Base\Livewire\Table\Traits;

trait Settings
{
    public function defaultSettings()
    {
        return [
            'per_page' => 10,
            'columns' => $this->model->getTableHeaders(),
            'filters' => [],
            'search' => '',
            'sort' => [
                'column' => 'id',
                'direction' => 'desc',
            ],
            'settings' => true,
            'sort_columns' => true,
            'sort_columns_key' => false,
            'sort_columns_user_key' => 'columns_sort.'.$this->model->getType(),
            'search' => true,
            'filters' => true,
            'global_filters' => true,
            'title' => true,
            'attach' => true,
            'selectable' => true,
            'default_view' => 'list',
            'header_before' => true,
            'header_after' => true,
            'table_before' => true,
            'table_after' => true,
            'create' => true,
            'actions' => true,
            'bulk_actions' => true,
            'header' => true,
            'views' => [
                'table' => 'aura::components.table.table',
                'list' => 'aura::components.table.list',
                'grid' => 'aura::components.table.grid',
                'filter' => 'aura::components.table.filter',
                'header' => 'aura::components.table.header',
                'row' => 'aura::components.table.row',
                'bulkActions' => 'aura::components.table.bulkActions',
            ],
        ];
    }

    public function initiateSettings()
    {
        $this->settings = $this->array_merge_recursive_distinct($this->defaultSettings(), $this->settings ?: []);
    }

    protected function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
