<?php

namespace Aura\Base\Traits;

trait InteractsWithTable
{
    public function defaultPerPage()
    {
        return 10;
    }

    public function defaultTableSort()
    {
        return 'id';
    }

    public function defaultTableSortDirection()
    {
        return 'desc';
    }

    public function defaultTableView()
    {
        return 'list';
    }

    public function kanbanQuery($query)
    {
        return false;
    }

    /**
     * Configure the resource's Kanban capability.
     *
     * Returning a Kanban view from tableKanbanView() continues to enable the
     * legacy status-based board. New resources should override this method.
     * Optional: column_total (a number field summed in each column header) and
     * card_fields (slugs shown on each card: relations as a chip, a user as an
     * avatar, dates with a calendar mark, anything else as its display value).
     *
     * @return array<string, mixed>
     */
    public function kanbanSettings(): array
    {
        return [
            'enabled' => is_string($this->tableKanbanView()) && $this->tableKanbanView() !== '',
            'group_field' => 'status',
            'columns' => [],
            'card_title' => 'title',
            'card_subtitle' => null,
            'order_by' => null,
            'show_empty_columns' => true,
            'column_total' => null,
            'card_fields' => [],
        ];
    }

    public function showTableSettings()
    {
        return true;
    }

    public function tableGridView()
    {
        return false;
    }

    public function tableKanbanView()
    {
        return false;
    }

    public function tableRowView()
    {
        return 'attachment.row';
    }

    public function tableView()
    {
        return 'aura::components.table.list-view';
    }
}
