<?php

namespace Eminiarts\Aura\Livewire\Taxonomy;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\HasActions;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $resource;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        $this->model = Aura::findTaxonomyBySlug($slug)->find($id);

        // Authorize
        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->resource = $this->model->attributesToArray();

        $this->resource['terms'] = $this->model->terms;
        $this->resource['terms']['tag'] = $this->resource['terms']['tag'] ?? null;
        $this->resource['terms']['category'] = $this->resource['terms']['category'] ?? null;
    }

    public function render()
    {
        // $view = "aura.{$this->slug}.view";

        // // if aura::aura.{$post->type}.view is set, use that view
        // if (view()->exists($view)) {
        //     return view($view)->layout('aura::components.layout.app');
        // }

        // if (view()->exists("aura::" . $view)) {
        //     return view("aura::" . $view)->layout('aura::components.layout.app');
        // }

        return view('aura::livewire.resource.view')->layout('aura::components.layout.app');
    }
}
