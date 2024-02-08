<?php

namespace Eminiarts\Aura\Livewire\Resource;

use Eminiarts\Aura\Facades\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $resource;

    public $slug;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->resource = Aura::findResourceBySlug($slug);

        // if this post is null redirect to dashboard
        if (is_null($this->resource)) {
            return redirect()->route('aura.dashboard');
        }

        if (!$this->resource::$indexViewEnabled) {
            return redirect()->route('aura.dashboard');
        }

        // Authorize if the User can see this Post
        $this->authorize('viewAny', $this->resource);
    }

    public function render()
    {
        return view($this->resource->indexView())->layout('aura::components.layout.app');
    }
}
