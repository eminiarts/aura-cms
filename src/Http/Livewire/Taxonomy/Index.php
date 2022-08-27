<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura;
use Livewire\Component;

class Index extends Component
{
    public $taxonomy;

    public $slug;

    public function render()
    {
        return view('livewire.taxonomy.index');
    }

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->taxonomy = Aura::findTaxonomyBySlug($slug);

        // Authorize

        // Array instead of Eloquent Model
        // $this->post = $this->model->toArray();
    }
}
