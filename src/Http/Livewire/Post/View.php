<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use RepeaterFields;

    public $inModal = false;

    public $model;

    public $post;

    public $slug;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function getField($slug)
    {
        return $this->post['fields'][$slug];
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug)->find($id);

        // Authorize
        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->attributesToArray();

        $this->post['terms'] = $this->model->terms;
        $this->post['terms']['tag'] = $this->post['terms']['tag'] ?? null;
        $this->post['terms']['category'] = $this->post['terms']['category'] ?? null;
    }

    
    public function render()
    {
        return view('aura::livewire.post.view')->layout('aura::components.layout.app');
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

        $this->model->update($this->post);

        $this->notify('Successfully updated.');

        if ($this->inModal) {
            $this->emit('closeModal');
            $this->emit('refreshTable');
        }
    }
}
