<?php

namespace Aura\Base\Tests\Fixtures\Modals;

use Livewire\Component;

class ModalStub extends Component
{
    public function render(): string
    {
        return '<div data-test-modal-stub>Modal stub</div>';
    }
}
