<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura;
use Eminiarts\Aura\Traits\HasFields;
use Livewire\Component;

class Edit extends Component
{
    use HasFields;

    public $taxonomy;

    public $slug;

    public function render()
    {
        return view('livewire.taxonomy.edit');
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

    public function save()
    {
        $this->validate();

        // Set Fields
        $this->post['fields']['taxonomy'] = $this->slug;

        $this->model->update($this->post['fields']);

        $this->notify('Successfully updated.');
    }
}
