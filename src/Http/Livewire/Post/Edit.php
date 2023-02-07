<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Traits\InteractsWithFields;
use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

class Edit extends Component
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
        $this->model = app(Aura::class)->findResourceBySlug($slug)->find($id);

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

    public function removeMediaFromField($slug, $id)
    {
        $field = $this->getField($slug);

        $field = collect($field)->filter(function ($value) use ($id) {
            return $value != $id;
        })->values()->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $field,
        ]);
    }

    public function render()
    {
        return view('livewire.post.edit');
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        // emit update Field
        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
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

    // Select Attachment
    public function updateField($data)
    {
        $this->post['fields'][$data['slug']] = $data['value'];

        $this->save();
    }
}
