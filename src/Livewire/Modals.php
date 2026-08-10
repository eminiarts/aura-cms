<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Finder\Finder;

class Modals extends Component
{
    /** @var array<string, bool> */
    public array $activeModals = [];

    /** @var array<string, array<string, mixed>> */
    #[Locked]
    public array $modals = [];

    #[On('closeModal')]
    public function closeModal($id = null): void
    {
        if ($id) {
            unset($this->modals[$id]);
            unset($this->activeModals[$id]);
        } else {
            $this->modals = [];
            $this->activeModals = [];
        }
    }

    public function mount()
    {
        // Initialization logic if needed
    }

    #[On('openModal')]
    public function openModal(
        mixed $component,
        mixed $arguments = [],
        mixed $modalAttributes = [],
        ?ModalActionRegistry $actions = null,
        ?SignedModalRequest $signedRequests = null,
    ): void {
        if (! is_string($component) || ! is_array($arguments) || ! is_array($modalAttributes)) {
            abort(422, 'The modal request is invalid.');
        }

        $actions ??= app(ModalActionRegistry::class);
        $signedRequests ??= app(SignedModalRequest::class);
        $resolved = $signedRequests->supports($component)
            ? $signedRequests->resolve($component)
            : $actions->resolve($component, $arguments, $modalAttributes);
        $component = $resolved['component'];
        $arguments = $resolved['arguments'];
        $modalAttributes = $resolved['modalAttributes'];
        $id = md5($component.serialize($arguments));

        // Resolve component class - handle both namespaced and non-namespaced components
        $componentClass = null;
        try {
            $componentClass = app(Finder::class)->resolveClassComponentClassName($component);
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
        ];
        $this->activeModals[$id] = true;
    }

    public function render()
    {
        // ray($this->modals)->blue(); // This will show the contents of $modals in Ray

        return view('aura::livewire.modals');
    }
}
