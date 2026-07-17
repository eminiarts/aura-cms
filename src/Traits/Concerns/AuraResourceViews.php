<?php

namespace Aura\Base\Traits\Concerns;

trait AuraResourceViews
{
    public function createView()
    {
        return 'aura::livewire.resource.create';
    }

    public function editHeaderView()
    {
        return 'aura::livewire.resource.edit-header';
    }

    public function editView()
    {
        return 'aura::livewire.resource.edit';
    }

    public function indexView()
    {
        return 'aura::livewire.resource.index';
    }

    public function rowView()
    {
        return 'aura::components.table.row';
    }

    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }

    public function viewHeaderView()
    {
        return 'aura::livewire.resource.view-header';
    }

    public function viewView()
    {
        return 'aura::livewire.resource.view';
    }
}
