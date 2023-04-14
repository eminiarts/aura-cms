<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use Eminiarts\Aura\Facades\Aura;
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
            return redirect()->route('aura.dashboard');
        }

        // Authorize if the User can see this Post
        // ray('hierer', $this->post);
        $this->authorize('viewAny', $this->post);

        $this->fields = $this->post->inputFields();
    }

    public function render()
    {
        return view('aura::livewire.post.index')->layout('aura::components.layout.app');
    }
}
