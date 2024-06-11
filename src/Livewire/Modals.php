<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Modals extends Component
{
    protected $listeners = ['openModal', 'closeModal'];

    public $modals = [];

    public $activeModal = false;

    
    public function mount()
    {
        // ray('mount');
    }

    public function render()
    {
        return view('aura::livewire.modals');
    }

    public function closeModal(): void
    {
        $this->activeModal = false;
    }

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $id = md5($component.serialize($arguments));

        ray('openModal', $id, $component, $arguments, $modalAttributes);

        $this->modals[$id] = [
            'name' => $component,
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'persistent' => false,
                'maxWidth' => 'md',
                'maxWidthClass' => 'max-w-3xl',
                // 'closeOnClickAway' => true,
                // 'closeOnEscape' => true,
                // 'closeOnEscapeIsForceful' => true,
                // 'dispatchCloseEvent' => true,
                // 'destroyOnClose' => true,
                // 'maxWidth' => 'md',
                // 'maxWidthClass' => 'max-w-3xl',
            ], $modalAttributes),
        ];

        ray($id);

        $this->activeModal = $id;

        // $this->dispatch('activeModalComponentChanged', id: $id);
    }
}
