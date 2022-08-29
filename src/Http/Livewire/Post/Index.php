<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura\Aura;
use Livewire\Component;

class Index extends Component
{
    public $post;

    public $slug;

    public $fields;

    public function render()
    {
        return view('livewire.post.index');
    }

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->post = Aura::findResourceBySlug($slug);
        $this->fields = $this->post->inputFields();

        // dd($this->post);
        // dd($this->post->getFields());
    }
}
