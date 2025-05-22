<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;
    use WithFileUploads;

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
        // dd($method, $params, $captureReturnValueCallback);
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

    public function mount($slug = null)
    {
        $this->slug = $slug;

        if (! $this->slug) {
            $routeName = request()->route()->getName();
            $this->slug = explode('.', $routeName)[1] ?? null;
        }

        $this->model = Aura::findResourceBySlug($this->slug);

        // Authorize
        $this->authorize('create', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->toArray();

        // Initialize the post fields with defaults
        $this->initializeFieldsWithDefaults();

        // Get all URL parameters
        $urlParameters = request()->query();

        // Process each URL parameter
        foreach ($urlParameters as $key => $value) {
            // Check if this parameter corresponds to a form field
            if (array_key_exists($key, $this->form['fields'])) {
                // If the value is already an array, use it directly
                if (is_array($value)) {
                    $this->form['fields'][$key] = array_map(function ($v) {
                        return is_numeric($v) ? (int) $v : $v;
                    }, $value);
                } else {
                    // If it's a single value, convert to integer if numeric
                    $this->form['fields'][$key] = is_numeric($value) ? (int) $value : $value;
                }
            }
        }
    }

    public function render()
    {

        return view($this->model->createView())->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $rules = collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();

        // Modify rules if the model implements it
        if (method_exists($this->model, 'modifyValidationRules')) {
            $rules = $this->model->modifyValidationRules($rules, $this->form, $this);
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

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
            return redirect()->route('aura.'.$this->slug.'.edit', $model->id);
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
