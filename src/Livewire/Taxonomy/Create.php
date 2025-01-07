<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use LivewireUI\Modal\ModalComponent;

class Create extends ModalComponent
{
    use AuthorizesRequests;
    use InteractsWithFields;

    public $inModal = false;

    public $model;

    public $resource;

    public $slug;

    public function mount($slug, $id = null)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug);

        // Authorize if the User can create
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->resource = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.create');
    }

    public function rules()
    {
        return Arr::dot([
            'resource.terms' => '',
            'form.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->form['fields']['taxonomy'] = $this->slug;

        $model = $this->model->create($this->form['fields']);

        $this->closeModal();

        $this->notify('Successfully created.');
    }
}
