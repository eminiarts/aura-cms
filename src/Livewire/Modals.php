<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Modals extends Component
{
    public $modals = [];

    /** @var array<string, array{name: string, ownerToken: mixed}> */
    #[Locked]
    public array $modalSecurity = [];

    #[On('closeModal')]
    public function closeModal($id = null): void
    {
        if ($id) {
            if (isset($this->modals[$id]) && $this->dismissalLocked((string) $id)) {
                return;
            }

            unset($this->modals[$id]);
            unset($this->modalSecurity[$id]);
        } else {
            foreach (array_keys($this->modals) as $modalId) {
                if ($this->dismissalLocked((string) $modalId)) {
                    return;
                }
            }

            $this->modals = [];
            $this->modalSecurity = [];
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

        // Resolve component class - handle both namespaced and non-namespaced components
        $componentClass = null;
        try {
            [, $componentClass] = app('livewire.factory')->resolveComponentNameAndClass($component);
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

        if ($component === ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID) {
            $this->modals[$id]['modalAttributes']['persistent'] = true;
        }

        $this->modalSecurity[$id] = [
            'name' => (string) $component,
            'ownerToken' => data_get($arguments, 'ownerToken'),
        ];
    }

    public function render()
    {
        // ray($this->modals)->blue(); // This will show the contents of $modals in Ray

        return view('aura::livewire.modals');
    }

    public function updatedModals(mixed $value, string $key): void
    {
        if ($value !== false || ! str_ends_with($key, '.active')) {
            return;
        }

        $id = substr($key, 0, -strlen('.active'));

        if (isset($this->modals[$id]) && $this->dismissalLocked($id)) {
            $this->modals[$id]['active'] = true;
        }
    }

    public function updatingModals(mixed $value, ?string $key = null): void
    {
        if (! is_string($key) || ! str_ends_with($key, '.active')) {
            abort(403, 'Modal metadata is server-owned.');
        }

        $id = substr($key, 0, -strlen('.active'));

        if (! array_key_exists($id, $this->modals) || ! is_bool($value)) {
            abort(403, 'Modal state is invalid.');
        }

        if ($value === false && $this->dismissalLocked($id)) {
            abort(403, 'This modal cannot be dismissed while its request is pending.');
        }
    }

    private function dismissalLocked(string $id): bool
    {
        $modal = $this->modalSecurity[$id] ?? $this->modals[$id] ?? [];

        if (($modal['name'] ?? null) !== ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID) {
            return false;
        }

        $ownerToken = $modal['ownerToken'] ?? data_get($modal, 'arguments.ownerToken');
        $actor = auth()->user();

        if (! is_string($ownerToken) || ! $actor instanceof Authenticatable) {
            return true;
        }

        try {
            return app(MediaSelectionBroker::class)->hasActiveRequestForOwner($ownerToken, $actor);
        } catch (Throwable) {
            return true;
        }
    }
}
