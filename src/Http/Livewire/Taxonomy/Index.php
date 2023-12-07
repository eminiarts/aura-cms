<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura\Facades\Aura;
use Livewire\Component;

class Index extends Component
{
    public $slug;

    public $taxonomy;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->taxonomy = Aura::findTaxonomyBySlug($slug);

        abort_if(!$this->taxonomy, 404, 'Taxonomy not found.');
    }

    public function render()
    {
        return view('aura::livewire.taxonomy.index')->layout('aura::components.layout.app');
    }
}
