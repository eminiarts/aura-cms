<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura;
use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Traits\HasFields;
use Eminiarts\Aura\Traits\RepeaterFields;

class Create extends Component
{
    use HasFields;
    use RepeaterFields;

    public $post;

    public $slug;

    public $model;

    public $tax;

    public function render()
    {
        return view('livewire.post.create');
    }

    public function rules()
    {
        return Arr::dot([
            'post.terms' => '',
            'post.fields' => $this->model->validationRules(),
        ]);
    }

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->model = Aura::findResourceBySlug($slug);

        // Authorize

        // Array instead of Eloquent Model
        $this->post = $this->model->toArray();

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
        $this->validate();

        // dd($this->post);

        $this->model->save();

        $this->model->update($this->post);

        $this->notify('Successfully created.');
    }

    public function getTaxonomiesProperty()
    {
        return $this->model->getTaxonomies();
    }
}
