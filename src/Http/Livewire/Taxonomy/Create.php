<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use LivewireUI\Modal\ModalComponent;

class Create extends ModalComponent
{
    use Aura\Traits\InteractsWithFields;
    use AuthorizesRequests;

    public $model;

    public $post;

    public $slug;

    public function mount($slug, $id = null)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug);

        // Authorize if the User can create
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();
    }

    public function render()
    {
        return view('livewire.taxonomy.create');
    }

    public function rules()
    {
        return Arr::dot([
            'post.terms' => '',
            'post.fields' => $this->model->validationRules(),
        ]);
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
