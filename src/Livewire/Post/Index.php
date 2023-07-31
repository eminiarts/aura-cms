<?php

namespace Eminiarts\Aura\Livewire\Post;

use Eminiarts\Aura\Facades\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $fields;

    protected $post;

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
        $this->authorize('viewAny', $this->post);

        $this->fields = '';
        // $this->fields = $this->post->inputFields();

        // dd($this->fields);
    }

    public function render()
    {
        return view('aura::livewire.post.index', ['post' => $this->post])->layout('aura::components.layout.app');
    }
}
