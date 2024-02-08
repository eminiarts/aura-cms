<?php

namespace Eminiarts\Aura\Livewire\Taxonomy;

use Illuminate\Support\Arr;
use Eminiarts\Aura\Facades\Aura;
use LivewireUI\Modal\ModalComponent;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
            'resource.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->resource['fields']['taxonomy'] = $this->slug;

        // dd($this->resource, $this->model);

        $model = $this->model->create($this->resource['fields']);

        // dd('hier', $model);

        $this->closeModal();

        $this->notify('Successfully created.');
    }
}
