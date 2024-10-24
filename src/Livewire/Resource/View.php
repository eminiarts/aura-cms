<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use RepeaterFields;

    public $form;

    public $inModal = false;

    public $mode = 'view';

    public $model;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = [
        'updateField' => 'updateField',
        'refreshComponent' => '$refresh',
        'reload',
    ];

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function mount($id)
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();
        $this->slug = explode('.', $routeName)[1] ?? null;

        $this->model = Aura::findResourceBySlug($this->slug)->find($id);

        // Authorize
        // ray($this->model, $slug, auth()->user());

        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        $this->form['terms'] = $this->model->terms;
        $this->form['terms']['tag'] = $this->form['terms']['tag'] ?? null;
        $this->form['terms']['category'] = $this->form['terms']['category'] ?? null;
    }

    public function reload()
    {
        $this->model = $this->model->fresh();
        $this->form = $this->model->attributesToArray();

        $this->dispatch('refreshComponent');
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
        return view($this->model->viewView())->layout('aura::components.layout.app');

    }
}
