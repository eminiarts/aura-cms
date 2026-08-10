<?php

namespace Aura\Base\Tests\Fixtures\ComponentSlots;

use Livewire\Component;

class PluginGlobalSearch extends Component
{
    public string $query = '';

    public function render(): string
    {
        return '<div>Plugin search: '.$this->query.'</div>';
    }

    public function runSearch(string $query): void
    {
        $this->query = $query;
    }
}
