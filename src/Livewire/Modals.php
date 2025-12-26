<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Modals extends Component
{
    public $modals = [];

    #[On('closeModal')]
    public function closeModal($id = null): void
    {
        if ($id) {
            unset($this->modals[$id]);
        } else {
            $this->modals = [];
        }
    }

    public function mount()
    {
        // Initialization logic if needed
    }

    #[On('openModal')]
    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $id = md5($component.serialize($arguments));

        $componentClass = app('livewire.finder')->resolveClassComponentClassName($component);

        // Determine modal classes - only check method_exists if we have a valid class
        $modalClasses = 'max-w-4xl';
        if ($componentClass !== null && method_exists($componentClass, 'modalClasses')) {
            $modalClasses = $componentClass::modalClasses();
        }

        $this->modals[$id] = [
            'name' => $component,
            'arguments' => $arguments,
            'modalAttributes' => array_merge([
                'persistent' => false,
                'modalClasses' => $modalClasses,
                'slideOver' => false,
            ], $modalAttributes),
            'active' => true,
        ];
    }

    public function render()
    {
        // ray($this->modals)->blue(); // This will show the contents of $modals in Ray

        return view('aura::livewire.modals');
    }
}
