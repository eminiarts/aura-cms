<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use LivewireUI\Modal\ModalComponent;

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
        return view('aura::livewire.taxonomy.create')->layout('aura::components.layout.app');
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
