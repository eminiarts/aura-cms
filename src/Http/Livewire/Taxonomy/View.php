<?php

namespace Eminiarts\Aura\Http\Livewire\Taxonomy;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $post;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function getField($slug)
    {
        return $this->post['fields'][$slug];
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        $this->model = Aura::findTaxonomyBySlug($slug)->find($id);

        // Authorize
        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->attributesToArray();

        $this->post['terms'] = $this->model->terms;
        $this->post['terms']['tag'] = $this->post['terms']['tag'] ?? null;
        $this->post['terms']['category'] = $this->post['terms']['category'] ?? null;
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

        return view('aura::livewire.post.view')->layout('aura::components.layout.app');
    }
}