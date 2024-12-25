<?php

namespace Aura\Base\Livewire\Table\Traits;

trait Settings
{
    public function defaultSettings()
    {
        return [
            'per_page' => 10,
            'columns' => $this->model->getTableHeaders(),
            'search' => '',
            'sort' => [
                'column' => 'id',
                'direction' => 'desc',
            ],
            'settings' => true,
            'sort_columns' => true,
            'columns_global_key' => false,
            'columns_user_key' => 'columns.'.$this->model->getType(),
            'search' => true,
            'filters' => true,
            'global_filters' => true,
            'title' => true,
            'selectable' => true,
            'default_view' => $this->model->defaultTableView(),
            // 'current_view' => $this->model->defaultTableView(),
            'header_before' => true,
            'header_after' => true,
            'table_before' => true,
            'table_after' => true,
            'create' => true,
            'create_url' => null,
            'actions' => true,
            'bulk_actions' => true,
            'header' => true,
            'edit_in_modal' => false,
            'create_in_modal' => false,
            'views' => [
                'table' => 'aura::components.table.index',
                'list' => $this->model->tableView(),
                'grid' => $this->model->tableGridView(),
                'kanban' => $this->model->tableKanbanView(),
                'filter' => 'aura::components.table.filter',
                'header' => 'aura::components.table.header',
                'row' => $this->model->rowView(),
                'bulk_actions' => 'aura::components.table.bulk-actions',
                'table_header' => 'aura::components.table.table-header',
                'table_footer' => 'aura::components.table.footer',
                'filter_tabs' => 'aura::components.table.filter-tabs',
            ],
        ];
    }

    public function mountSettings()
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
