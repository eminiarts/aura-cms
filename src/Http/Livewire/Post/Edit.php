<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Livewire\Component;
use Illuminate\Support\Arr;
use Livewire\WithFileUploads;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\HasActions;
use Eminiarts\Aura\Traits\MediaFields;
use Illuminate\Support\Traits\Macroable;
use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;
    // use Macroable;
    use WithFileUploads;

    public $inModal = false;

    public $model;

    public $post;

    public $slug;

    public $tab;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = [
        'updateField' => 'updateField',
        'saveModel' => 'save',
        'refreshComponent' => '$refresh',
        'reload',
    ];

    public function callMethod($method, $params = [], $captureReturnValueCallback = null)
    {
        // If the method exists in this component, call it directly.
        if (method_exists($this, $method) || !optional($params)[0]) {
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
                $post = call_user_func_array([$fieldTypeInstance, $method], array_merge([$this->model, $this->post], $params));

                // If the field type method returns a post, update the post.
                if ($post) {
                    $this->post = $post;
                }

                // Make sure to return here, otherwise the parent callMethod will be called.
                return;
            }
        }

        // Run parent callMethod
        return parent::callMethod($method, $params, $captureReturnValueCallback);
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function initializeModelFields()
    {
        foreach ($this->model->inputFields() as $field) {
            // If the method exists in the field type, call it directly.
            if (method_exists($field['field'], 'hydrate')) {
                $this->post['fields'][$field['slug']] = $field['field']->hydrate();
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
        $this->post = $this->model->attributesToArray();

        // foreach fields, call the hydration method on the field
        $this->initializeModelFields();

        // foreach fields, call the hydration method on the field

        $this->post['terms'] = $this->model->terms;

        // dd('mount', $this->post, $this->model);

        // Set on model instead of here
        // if $this->post['terms']['tag'] is not set, set it to null
        $this->post['terms']['tag'] = $this->post['terms']['tag'] ?? null;
        $this->post['terms']['category'] = $this->post['terms']['category'] ?? null;

    }

    public function reload()
    {
        $this->model = $this->model->fresh();
        $this->post = $this->model->attributesToArray();
        // The GET method is not supported for this route. Only POST is supported.
        // Therefore, we cannot use redirect()->to(url()->current()).
        // Instead, we will refresh the component.
        $this->emit('refreshComponent');
    }

    public function render()
    {
        return view($this->model->editView())->layout('aura::components.layout.app');
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

        unset($this->post['fields']['group']);

        if (isset($this->post['fields']['mzkb_pdf'])) {
            $mzkb_pdf = $this->post['fields']['mzkb_pdf'];
            if (! is_string($mzkb_pdf)) {
                $mzkb_pdfPath = $mzkb_pdf->store('profile', 'public');
                $this->post['fields']['mzkb_pdf'] = $mzkb_pdfPath;
            }
        }

        // ray('after validate');

        // dd('save', $this->post, $this->rules());

        // unset this post fields group

        if ($this->model->usesCustomTable()) {

            // ray('custom table');

            // dd('save', $this->post['fields']);

            // ray('update', $this->post['fields']);

            $this->model->update($this->post['fields']);
        // $this->model->update(['fields' => $this->post['fields']]);

        // $this->model->update($this->post['fields']);
        } else {
            // ray('no custom table');
            // ray($this->post);
            $this->model->update($this->post);
        }

        // ray('after update');

        $this->notify(__('Successfully updated'));

        if ($this->inModal) {
            $this->emit('closeModal');
            $this->emit('refreshTable');
        }
        // ray('refresh');

        $this->emit('refreshComponent');

        // Redirect to edit page

        // ray('before redirect');

        // $this->redirect(route('aura.post.edit', [$this->slug, $this->model->id]));
        $this->model = $this->model->fresh();
        $this->post = $this->model->attributesToArray();

    }

    public function updatedPost($value, $array)
    {
        // dd('updatedPostFields', $value, $array, $this->post);
    }
}
