<?php

namespace Eminiarts\Aura\Livewire\Post;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\MediaFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $params;

    public $post;

    public $slug;

    public $tax;

    protected $listeners = ['updateField' => 'updateField'];

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        // dd($this->model->createFields());
        //dd($this->model);

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

            // if there is a key in post's fields named $this->params['for'], set it to $this->params['id']
            if (optional($this->params)['for'] && optional($this->params)['id'] && array_key_exists($this->params['for'], $this->post['fields'])) {
                $this->post['fields'][$this->params['for']] = (int) $this->params['id'];
            }
        }

        // If $for is "User", set the user_id to the $id
        // This needs to be more dynamic, but it works for now
        if ($for == 'User') {
            $this->post['fields']['user_id'] = (int) $id;
        }
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
        $this->validate();

        $model = $this->model->create($this->post);

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');

            if ($this->params['for']) {
                $this->dispatch('resourceCreated', ['for' => $this->params['for'], 'resource' => $model, 'title' => $model->title()]);
            }
        } else {
            return redirect()->route('aura.post.edit', [$this->slug, $model->id]);
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }


}
