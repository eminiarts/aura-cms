<?php

namespace Aura\Base\Livewire;

use Livewire\Component;

class Modals extends Component
{
    public $activeModals = [];

    public $modals = [];

    protected $listeners = ['openModal', 'closeModal'];

    public function closeModal($id = null): void
    {
        if ($id) {
            unset($this->modals[$id]);
            $this->activeModals = array_values(array_filter($this->activeModals, function ($modalId) use ($id) {
                return $modalId !== $id;
            }));
        } else {
            $this->modals = [];
            $this->activeModals = [];
        }
    }

    public function mount()
    {
        // Initialization logic if needed
    }

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $id = md5($component.serialize($arguments));

        $this->modals[$id] = [
            'name' => $component,
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'persistent' => false,
                'maxWidth' => 'md',
                'maxWidthClass' => 'max-w-3xl',
                'slideOver' => false,
            ], $modalAttributes),
        ];
        $this->activeModals[$id] = true;

        ray($this->modals, $this->activeModals);

    }

    public function render()
    {
        return view('aura::livewire.modals');
    }
}
