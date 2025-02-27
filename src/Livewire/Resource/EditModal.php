<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;

class EditModal extends Edit
{
    public $resource;

    public $type;

    public function mount($id = null, $resource = null, $type = null)
    {
        ray('mount', $id, $resource, $type);

        if ($resource && $type) {
            $this->resource = $resource;
            $this->type = $type;
            $this->slug = $type;

            $this->model = Aura::findResourceBySlug($this->slug)->find($resource);

            // dd($this->model);

            if ($this->model) {
                $this->authorize('update', $this->model);
                $this->form = $this->model->attributesToArray();
                $this->initializeModelFields();
            }

            ray('here', $this->model);
        } else {
            // parent::mount($id);
        }
    }

    public function render()
    {
        return view('aura::livewire.resource.edit-modal');
    }
}
