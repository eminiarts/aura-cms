<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Livewire\Attributes\On;

class ViewModal extends View
{
    public $loading = true;

    public $modalAttributes = [
        'persistent' => true,
        'slideOver' => true,
    ];

    public $resource;

    public $type;

    // Track whether this modal has been properly initialized
    protected $initialized = false;

    public function dehydrate()
    {
        // Ensure the modal stays open by dispatching events
        $this->dispatch('modalInitialized');
        $this->dispatch('forceKeepOpen');

        // Critical: prevent any other modals from opening
        $this->dispatch('suppressOtherModals');
    }

    /**
     * Handle the showSaveFilterModal event by preventing it when our modal is active
     */
    #[On('showSaveFilterModal')]
    public function handleSaveFilterModal($show = true)
    {
        // Intercept the save filter modal and prevent it from showing
        if ($this->initialized) {
            $this->dispatch('forceKeepOpen');

            return false;
        }

        return true;
    }

    /**
     * Keep the modal open by dispatching events every few milliseconds
     */
    #[On('keepModalOpen')]
    public function keepOpen()
    {
        // This method will be triggered by JavaScript to keep the modal open
        $this->dispatch('forceKeepOpen');
        $this->dispatch('suppressOtherModals');
    }

    public static function modalClasses()
    {
        return 'max-w-4xl';
    }

    public function mount($id = null, $resource = null, $type = null, $modalAttributes = [])
    {
        // Store the modal attributes, ensuring persistent is always true
        $this->modalAttributes = array_merge($this->modalAttributes, $modalAttributes, ['persistent' => true]);

        if ($resource && $type) {
            $this->resource = $resource;
            $this->type = $type;
            $this->slug = $type;
            $this->inModal = true;

            try {
                $this->model = Aura::findResourceBySlug($this->slug)->find($resource);

                if ($this->model) {
                    $this->authorize('view', $this->model);
                    $this->form = $this->model->attributesToArray();

                    // If the model has an initializeModelFields method, call it
                    if (method_exists($this, 'initializeModelFields')) {
                        $this->initializeModelFields();
                    }

                    // Successfully initialized
                    $this->initialized = true;
                }
            } catch (\Exception $e) {
                // Log the error but don't crash
                logger()->error('ViewModal initialization error: '.$e->getMessage());
            }
        } else {
            parent::mount($id);
            $this->initialized = true;
        }

        // Mark as loaded after initialization
        $this->loading = false;
    }

    public function render()
    {
        return view('aura::livewire.resource.view-modal');
    }

    protected function initializeModelFields()
    {
        if (method_exists($this->model, 'inputFields')) {
            foreach ($this->model->inputFields() as $field) {
                // If the method exists in the field type, call it directly.
                if (method_exists($field['field'], 'hydrate') && isset($this->form['fields'][$field['slug']])) {
                    $this->form['fields'][$field['slug']] = $field['field']->hydrate($this->form['fields'][$field['slug']], $field);
                }
            }
        }
    }
}
