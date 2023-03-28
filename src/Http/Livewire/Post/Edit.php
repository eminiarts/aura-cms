<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\MediaFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;
    use InteractsWithFields;
    use RepeaterFields;
    use MediaFields;

    public $inModal = false;

    public $model;

    public $post;

    public $slug;

    public $tab;

    public $tax;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public function getActionsProperty()
    {
        return $this->model->getActions();
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;

        // dd($slug);

        $this->model = Aura::findResourceBySlug($slug)->find($id);

        // dd($this->model->toArray());

        // Authorize
        $this->authorize('update', $this->model);

        // Array instead of Eloquent Model
        $this->post = $this->model->attributesToArray();

        // dd($this->post);

        $this->post['terms'] = $this->model->terms;

        //dd($this->post->taxonomies);
        // dd($this->post, $this->model->terms);

        // Set on model instead of here
        // if $this->post['terms']['tag'] is not set, set it to null
        $this->post['terms']['tag'] = $this->post['terms']['tag'] ?? null;
        $this->post['terms']['category'] = $this->post['terms']['category'] ?? null;
    }



    public function render()
    {
        return view('aura::livewire.post.edit')->layout('aura::components.layout.app');
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
        // dump('saving', $this->post);
        // dd($this->rules(), $this->post);

        $this->validate();

        $this->model->update($this->post);

        $this->notify('Successfully updated.');

        if ($this->inModal) {
            $this->emit('closeModal');
            $this->emit('refreshTable');
        }
    }

    public function singleAction($action)
    {
        $this->model->{$action}();

        $this->notify('Successfully ran: '.$action);
    }

}
