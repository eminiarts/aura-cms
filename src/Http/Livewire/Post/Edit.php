<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura;
use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Arr;
use Livewire\WithFileUploads;
use Eminiarts\Aura\Traits\HasFields;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Traits\RepeaterFields;
use Livewire\TemporaryUploadedFile;

class Edit extends Component
{
    use HasFields;
    use RepeaterFields;
    use WithFileUploads;

    public $post;

    public $slug;

    public $model;

    public $tax;

    public function render()
    {
        return view('livewire.post.edit');
    }

    public function rules()
    {
        return Arr::dot([
            'post.terms' => '',
            'post.fields' => $this->model->validationRules(),
        ]);
    }

    public function mount($slug, $id)
    {
        $this->slug = $slug;
        $this->model = Aura::findResourceBySlug($slug)->find($id);

        // Authorize

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();

        // dd($this->post, $this->model->meta);

        $this->post['terms'] = $this->model->terms;

        //dd($this->post->taxonomies);
        // dd($this->post, $this->model->terms);

        // Set on model instead of here
        // $this->post['taxonomies']['tag'] = '';
        // $this->post['taxonomies']['category'] = '';

        // dd($this->model);
        // dd($this->model->taxonomies);
        // $this->post['taxonomies']['tag'] = $this->model->taxonomies->pluck('id');
    }

    public function save()
    {
        //  dd($this->post);

        // dd($this->rules(), $this->post);

        $this->validate();

        // Upload Files
        foreach ($this->post['fields'] as $key => $field) {
            if ($field instanceof TemporaryUploadedFile) {
                $this->post['fields'][$key] = $this->uploadFile($field);
            }
        }

        $this->model->update($this->post);

        $this->notify('Successfully updated.');
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }

    public function uploadFile($field)
    {
        $url = $field->store('media');

        $media = [
            'title' => $field->getClientOriginalName(),
            'content' => $url,
            'fields' => [
                'extension' => $field->extension(),
                'mime_type' => $field->getClientMimeType(),
                'size' => $field->getSize(),
                'file' => $url,
            ],
        ];

        $attachment = Attachment::create($media);

        return $attachment->id;
    }
}
