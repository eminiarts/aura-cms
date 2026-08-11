<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Facades\Aura;
use Aura\Base\ResourcePersistence\ResourceWriter;
use Aura\Base\Traits\HydratesResourceFormFields;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use AuthorizesRequests;
    use HydratesResourceFormFields;
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

        // Authorize - only if we have a model
        if ($this->model) {
            $this->authorize('create', $this->model);
        } else {
            // If no model found, we can't proceed
            return;
        }

        // Initialize form structure based on resource type
        if ($this->model->usesCustomTable()) {
            // For custom table resources, only fields are needed
            $this->form = [
                'fields' => [],
            ];
        } else {
            // For posts table resources, initialize with full post structure
            $this->form = [
                'title' => null,
                'content' => null,
                'status' => null,
                'slug' => null,
                'user_id' => auth()->id(),
                'parent_id' => null,
                'order' => null,
                'team_id' => config('aura.teams') ? data_get(auth()->user(), 'current_team_id') : null,
                'type' => $this->model::$type ?? null,
                'fields' => [],
            ];
        }

        $this->initializeFieldsWithDefaults();

        // Get all URL parameters
        $urlParameters = request()->query();

        // Process each URL parameter
        // Form values stay in their submitted representation. The field value
        // contract normalizes them immediately before persistence, so decimal
        // strings must not be cast to int here.
        $this->applyResourceFormFieldValues($urlParameters);

        // Process modal params (for modal usage) - similar to URL parameters
        if (isset($this->params) && is_array($this->params)) {
            $this->applyResourceFormFieldValues($this->params);
        }
    }

    public function render()
    {

        return view($this->model->createView())->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $visibleSlugs = $this->writableResourceFormFields(FieldValueContext::Create)->pluck('slug');

        $rules = collect($this->model->validationRules())
            ->filter(fn ($rule, $key) => $visibleSlugs->contains($key))
            ->mapWithKeys(function ($rule, $key) {
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
        $this->authorize('create', $this->model);
        $this->authorizeRequestedGlobalFormIntent();
        $this->sanitizeResourceFormFields(FieldValueContext::Create);

        $validated = $this->validate();
        $this->validateMediaFieldsBeforePersistence();

        $attributes = $this->validatedFormFields($validated, $this->model->createFields());
        $globalIntent = $this->pullGlobalFormIntent($attributes);

        $userClass = config('aura.resources.user');
        if (config('aura.teams') && $this->model instanceof $userClass) {
            $this->model->setAttribute('current_team_id', data_get(auth()->user(), 'current_team_id'));
        }

        $writer = app(ResourceWriter::class);
        $model = $globalIntent === true
            ? $writer->createGlobal($this->model, $attributes)
            : $writer->create($this->model, $attributes);

        $this->notify('Successfully created.');

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');

            if (optional($this->params)['for']) {
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

    protected function initializeFieldsWithDefaults(): void
    {
        $this->hydrateResourceFormFields(FieldValueContext::Create);
    }
}
