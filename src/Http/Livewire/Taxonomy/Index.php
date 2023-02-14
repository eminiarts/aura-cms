<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura\Aura;
use Livewire\Component;

class Index extends Component
{
    public $slug;

    public $taxonomy;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->taxonomy = Aura::findTaxonomyBySlug($slug);

        // Authorize

        // Array instead of Eloquent Model
        // $this->post = $this->model->toArray();
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.index')->layout('aura::components.layout.app');
    }
}
