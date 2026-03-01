<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;

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
    public function openModal($component = null, $arguments = [], $modalAttributes = []): void
    {
        // Handle Livewire 3 dispatch format where all params come as a single array
        if (is_array($component) && isset($component['component'])) {
            $modalAttributes = $component['modalAttributes'] ?? [];
            $arguments = $component['arguments'] ?? [];
            $component = $component['component'];
        }

        $id = md5($component.serialize($arguments));

        // Resolve component class - handle both namespaced and non-namespaced components
        $componentClass = null;
        try {
            $componentClass = app(ComponentRegistry::class)->getClass($component);
        } catch (\Exception $e) {
            // Component not found, use default modal classes
        }

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
        return view('aura::livewire.modals');
    }
}
