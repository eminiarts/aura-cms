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
    /** @var array<string, bool> */
    public array $activeModals = [];

    /** @var array<string, array<string, mixed>> */
    #[Locked]
    public array $modals = [];

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
            unset($this->activeModals[$id]);
            unset($this->modalSecurity[$id]);
        } else {
            foreach (array_keys($this->modals) as $modalId) {
                if ($this->dismissalLocked((string) $modalId)) {
                    return;
                }
            }

            $this->modals = [];
            $this->activeModals = [];
            $this->modalSecurity = [];
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
        ];
        $this->activeModals[$id] = true;

        if ($this->isMediaManagerComponent($component)) {
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

        if (! $this->isMediaManagerComponent($modal['name'] ?? null)) {
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

    private function isMediaManagerComponent(mixed $component): bool
    {
        return is_string($component) && in_array($component, [
            ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'aura::media-manager',
            'aura.base.livewire.media-manager',
        ], true);
    }
}
