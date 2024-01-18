<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

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

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        // Authorize
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();

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

        // Initialize the post fields with defaults
        $this->initializeFieldsWithDefaults();

    }

    public function render()
    {
        return view('aura::livewire.post.create')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        // dd($this->rules());

        $this->validate();

        // dd('save', $this->post);

        if ($this->model->usesCustomTable()) {

            $model = $this->model->create($this->post['fields']);

        } else {

            $model = $this->model->create($this->post);

        }

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->emit('closeModal');
            $this->emit('refreshTable');

            if ($this->params['for']) {
                $this->emit('resourceCreated', ['for' => $this->params['for'], 'resource' => $model, 'title' => $model->title()]);
            }
        } else {
            return redirect()->route('aura.post.edit', [$this->slug, $model->id]);
        }
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    protected function initializeFieldsWithDefaults()
    {
        $fields = $this->model->getFields(); // Assume this returns the fields configurations

        foreach ($fields as $field) {
            $slug = $field['slug'] ?? null;
            if ($slug && ! isset($this->post['fields'][$slug]) && isset($field['default'])) {
                $this->post['fields'][$slug] = $field['default'];
            }
        }
    }
}
