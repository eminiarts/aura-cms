<?php

namespace Eminiarts\Aura\Traits;

trait InteractsWithTable
{
    public function defaultPerPage()
    {
        return 10;
    }

    public function defaultTableView()
    {
        return 'list';
    }

    public function defaultTableSort()
    {
        return 'id';
    }

    public function defaultTableSortDirection()
    {
        return 'desc';
    }

    public function showTableSettings()
    {
        return true;
    }

    public function tableGridView()
    {
        return false;
    }

    public function tableRowView()
    {
        return 'attachment.row';
    }

    public function tableView()
    {
        return 'aura::components.table.table';
    }
}
