<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura;
use Eminiarts\Aura\Traits\HasFields;
use LivewireUI\Modal\ModalComponent;

class Create extends ModalComponent
{
    use HasFields;

    public $post;

    public $slug;

    public $model;

    public function render()
    {
        return view('livewire.taxonomy.create');
    }

    public function mount($slug, $id = null)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug);

        // Authorize

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();
    }

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->post['fields']['taxonomy'] = $this->slug;

        $this->model->create($this->post['fields']);

        $this->closeModal();

        $this->notify('Successfully created.');
    }
}
