<?php

namespace Eminiarts\Aura\Livewire\Taxonomy;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\HasActions;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;

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
        $this->resource = $this->model->toArray();

        // dd($this->resource);
        $this->resource['fields'] = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.edit')->layout('aura::components.layout.app');
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

        $this->model->update($this->resource['fields']);

        $this->notify(__('Successfully updated'));
    }
}
