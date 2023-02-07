<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $fields;

    public $post;

    public $slug;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->post = Aura::findResourceBySlug($slug);

        // if this post is null redirect to dashboard
        if (is_null($this->post)) {
            return redirect()->route('dashboard');
        }

        // Authorize if the User can see this Post
        $this->authorize('viewAny', $this->post);

        $this->fields = $this->post->inputFields();
        // dd($this->fields);
    }

    public function render()
    {
        return view('livewire.post.index');
    }
}
