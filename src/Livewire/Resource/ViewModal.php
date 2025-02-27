<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;

class ViewModal extends View
{
    public $resource;

    public $type;

    public function mount($id = null, $resource = null, $type = null)
    {
        if ($resource && $type) {
            $this->resource = $resource;
            $this->type = $type;
            $this->slug = $type; // Set the slug from the type

            // Get the model directly instead of calling parent::mount
            $this->model = Aura::findResourceBySlug($this->slug)->find($resource);

            // Continue with the rest of the initialization
            if ($this->model) {
                $this->authorize('view', $this->model);
                $this->form = $this->model->attributesToArray();

                // Initialize terms if needed like in the parent class
                $this->form['terms'] = $this->model->terms;
                $this->form['terms']['tag'] = $this->form['terms']['tag'] ?? null;
                $this->form['terms']['category'] = $this->form['terms']['category'] ?? null;
            }
        } else {
            parent::mount($id);
        }
    }

    public function render()
    {
        return view('aura::livewire.resource.view-modal');
    }
}
