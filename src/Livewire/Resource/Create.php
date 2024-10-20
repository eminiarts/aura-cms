<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    public $form;

    public $inModal = false;

    public $mode = 'edit';

    public $model;

    public $params;

    public $showSaveButton = true;

    public $slug;

    public $tax;

    protected $listeners = ['updateField' => 'updateField'];

    public function callMethod($method, $params = [], $captureReturnValueCallback = null)
    {
        // If the method exists in this component, call it directly.
        if (method_exists($this, $method) || ! optional($params)[0]) {
            return parent::callMethod($method, $params, $captureReturnValueCallback);
        }

        // Assuming the first parameter is always the slug to identify the field.
        $slug = $params[0];

        // Get the corresponding field instance based on the slug.
        $field = $this->model->fieldBySlug($slug);

        // Forward the call to the field's method.
        if ($field) {

            $fieldTypeInstance = app($field['type']);

            // If the method exists in the field type, call it directly.
            if (method_exists($fieldTypeInstance, $method)) {
                $post = call_user_func_array([$fieldTypeInstance, $method], array_merge([$this->model, $this->form], $params));

                // If the field type method returns a post, update the post.
                if ($post) {
                    $this->form = $post;
                }

                // Make sure to return here, otherwise the parent callMethod will be called.
                return;
            }
        }

        // Run parent callMethod
        return parent::callMethod($method, $params, $captureReturnValueCallback);
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        // ray($this->model);

        // Authorize
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->toArray();

        // get "for" and "id" params from url
        $for = request()->get('for');
        $id = request()->get('id');

        // if params are set, set the post's "for" and "id" fields
        if ($this->params) {
            if ($this->params['for'] == 'User') {
                $this->form['fields']['user_id'] = (int) $this->params['id'];
            }

            // if there is a key in post's fields named $this->params['for'], set it to $this->params['id']
            if (optional($this->params)['for'] && optional($this->params)['id'] && array_key_exists($this->params['for'], $this->form['fields'])) {
                $this->form['fields'][$this->params['for']] = (int) $this->params['id'];
            }
        }

        // If $for is "User", set the user_id to the $id
        // This needs to be more dynamic, but it works for now
        if ($for == 'User') {
            $this->form['fields']['user_id'] = (int) $id;
        }

        // Initialize the post fields with defaults
        $this->initializeFieldsWithDefaults();

    }

    public function render()
    {
        return view('aura::livewire.resource.create')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();
    }

    public function save()
    {
        // dd($this->model->toArray(), $this->rules(), $this->model->validationRules());

        $this->validate();

        // dd('save', $this->form);

        if ($this->model->usesCustomTable()) {

            $model = $this->model->create($this->form['fields']);

        } else {

            $model = $this->model->create($this->form);

        }

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');

            if ($this->params['for']) {
                $this->dispatch('resourceCreated', ['for' => $this->params['for'], 'resource' => $model, 'title' => $model->title()]);
            }
        } else {
            return redirect()->route('aura.resource.edit', [$this->slug, $model->id]);
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

            if ($field['type'] == "Aura\Base\Fields\Boolean" && ! isset($field['default'])) {
                $this->form['fields'][$slug] = false;

                continue;
            }

            if ($slug && ! isset($this->form['fields'][$slug]) && isset($field['default'])) {

                if ($field['type'] == "Aura\Base\Fields\Checkbox" && isset($field['options']) && is_array($field['options']) && ! is_array($field['default'])) {
                    $field['default'] = [$field['default']];
                }

                $this->form['fields'][$slug] = $field['default'];
            }
        }
    }
}
