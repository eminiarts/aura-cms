<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\HasActions;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Edit extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use HasActions;

    public $inModal = false;

    public $slug;

    public $taxonomy;

    public function getActionsProperty()
    {
        return $this->model->getActions();
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;
        $this->model = Aura::findTaxonomyBySlug($slug)->find($id);

        // Authorize

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();

        // dd($this->post);
        $this->post['fields'] = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.edit')->layout('aura::components.layout.app');
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

        $this->model->update($this->post['fields']);

        $this->notify('Successfully updated.');
    }
}
