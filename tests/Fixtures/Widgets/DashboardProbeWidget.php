<?php

namespace Aura\Base\Tests\Fixtures\Widgets;

use Livewire\Component;

class DashboardProbeWidget extends Component
{
    public string $label = 'Class widget';

    public function render(): string
    {
        return '<section data-dashboard-probe>'.e($this->label).'</section>';
    }
}
