<?php

namespace Aura\Base\Livewire\Taxonomy;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
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
        $this->form['fields'] = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.edit')->layout('aura::components.layout.app');
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

        $this->model->update($this->form['fields']);

        $this->notify(__('Successfully updated'));
    }
}
