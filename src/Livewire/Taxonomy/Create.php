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
        return view('aura::livewire.taxonomy.create');
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

        // dd($this->post, $this->model);

        $model = $this->model->create($this->post['fields']);

        // dd('hier', $model);

        $this->closeModal();

        $this->notify('Successfully created.');
    }
}