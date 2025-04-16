<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Traits\Macroable;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @method void notify(string $message, string $type = 'success')
 */
class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    // use Macroable;
    use WithFileUploads;

    public $form;

    public $inModal = false;

    public $mode = 'edit';

    public $model;

    public $slug;

    public $tab;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = [
        'updateField' => 'updateField',
        'saveModel' => 'save',
        'refreshComponent' => '$refresh',
        'reload',
        'saveBeforeAction',
    ];

    public function hydrate()
    {
        // ray('edit hydrate');
        if ($this->model && isset($this->model->id)) {
            // $this->model = Auth::user();
            // ray($this->model);
        }
    }

    public function initializeModelFields()
    {
        foreach ($this->model->inputFields() as $field) {

            // If the method exists in the field type, call it directly.
            if (method_exists($field['field'], 'hydrate') && isset($this->form['fields'][$field['slug']])) {
                // dd('hier');
                $this->form['fields'][$field['slug']] = $field['field']->hydrate($this->form['fields'][$field['slug']], $field);
            }

            if ($field['field']->on_forms === false) {
                unset($this->form['fields'][$field['slug']]);
            }

            if (optional($field)['on_forms'] === false) {
                unset($this->form['fields'][$field['slug']]);
            }
        }
    }

    public function mount($id)
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();

        if (! $this->slug) {
            $this->slug = explode('.', $routeName)[1] ?? null;
        }

        $this->model = Aura::findResourceBySlug($this->slug)->find($id);

        // ray($this->model);

        // Authorize
        $this->authorize('update', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        // foreach fields, call the hydration method on the field
        $this->initializeModelFields();

        // foreach fields, call the hydration method on the field

        // Set on model instead of here
        // if $this->form['terms']['tag'] is not set, set it to null
    }

    public function reload()
    {
        $this->model = $this->model->fresh();
        // $this->form = $this->model->attributesToArray();
        // The GET method is not supported for this route. Only POST is supported.
        // Therefore, we cannot use redirect()->to(url()->current()).
        // Instead, we will refresh the component.
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view($this->model->editView())->layout('aura::components.layout.app');
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

        // unset this post fields group
        if ($this->model->usesCustomTable()) {
            $this->model->update($this->form['fields']);
        } else {
            $this->model->update($this->form);
        }

        $this->notify(__('Successfully updated'));

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');
        }

        $this->model = $this->model->refresh();
        $this->form = $this->model->attributesToArray();

        $this->dispatch('refreshComponent');
    }

    public function updatedPost($value, $array) {}

    protected function callComponentMethod($method, $params)
    {
        $callbacks = array_filter(
            $params,
            fn ($param) => $param instanceof Closure
        );

        $params = array_filter(
            $params,
            fn ($param) => ! $param instanceof Closure
        );

        $result = parent::callMethod($method, $params);

        foreach ($callbacks as $callback) {
            $callback($this);
        }

        return $result;
    }
}
