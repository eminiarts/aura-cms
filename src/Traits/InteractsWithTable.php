<?php

namespace Eminiarts\Aura\Aura\Traits;

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

    public function tableGridView()
    {
        return false;
    }

    public function tableRowView()
    {
        return 'attachment.row';
    }
}
