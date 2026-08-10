<?php

namespace Aura\Base\Tests\Fixtures\ComponentSlots;

use Livewire\Component;

class HostGlobalSearch extends Component
{
    public string $query = '';

    public function render(): string
    {
        return '<div>Host search: '.$this->query.'</div>';
    }

    public function runSearch(string $query): void
    {
        $this->query = $query;
    }
}
