<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Create extends Component
{
    use InteractsWithFields;
    use AuthorizesRequests;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $params;

    public $post;

    public $slug;

    public $tax;

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        // Authorize
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();

        $this->post['terms'] = $this->model->terms;

        // get "for" and "id" params from url
        $for = request()->get('for');
        $id = request()->get('id');

        // if params are set, set the post's "for" and "id" fields
        if ($this->params) {
            if ($this->params['for'] == 'User') {
                $this->post['fields']['user_id'] = (int) $this->params['id'];
            }
        }

        // If $for is "User", set the user_id to the $id
        // This needs to be more dynamic, but it works for now
        if ($for == 'User') {
            $this->post['fields']['user_id'] = (int) $id;
        }

        // dd($this->post);
    }

    public function render()
    {
        return view('aura::livewire.post.create')->layout('aura::components.layout.app');
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
        // dd($this->rules());
        $this->validate();

        $this->model->create($this->post);

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->emit('closeModal');
            $this->emit('refreshTable');
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }
}
