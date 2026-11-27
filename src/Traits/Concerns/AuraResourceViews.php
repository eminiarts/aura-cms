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

    /**
     * Slugs of the fields the record page promotes into its header, under the
     * title (a deal's amount and stage, an order's total and status). Null
     * picks the default: the first Status and the first Number field that are
     * shown in lists. An empty array promotes nothing.
     *
     * @return array<int, string>|null
     */
    public function recordHeaderFields(): ?array
    {
        return null;
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
