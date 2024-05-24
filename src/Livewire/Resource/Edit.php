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

    public function initializeModelFields()
    {
        foreach ($this->model->inputFields() as $field) {
            // If the method exists in the field type, call it directly.
            if (method_exists($field['field'], 'hydrate')) {
                $this->form['fields'][$field['slug']] = $field['field']->hydrate();
            }

            if ($field['field']->on_forms === false) {
                unset($this->form['fields'][$field['slug']]);
            }

            if (optional($field)['on_forms'] === false) {
                unset($this->form['fields'][$field['slug']]);
            }
        }
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug)->find($id);

        // Authorize
        $this->authorize('update', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        // dd($this->model->attributesToArray());

        // foreach fields, call the hydration method on the field
        $this->initializeModelFields();

        // foreach fields, call the hydration method on the field

        // ray('mount', $this->form, $this->model);

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
        return collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();
    }

    public function save()
    {
        $this->validate();

        //  ray()->clearScreen();
        //    dd('saving', $this->form, $this->model);
        //    ray('saving', $this->form, $this->model);

        unset($this->form['fields']['group']);

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

    public function updatedPost($value, $array)
    {
        // dd('updatedPostFields', $value, $array, $this->form);
    }

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
