<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $resource;

    public $slug;

    public function mount()
    {
        // Get the slug from the current route
        $routeName = request()->route()->getName();
        $this->slug = explode('.', $routeName)[1] ?? null;

        if (! $this->slug) {
            // If we couldn't extract the slug, redirect to dashboard
            return redirect()->route('aura.dashboard');
        }

        $this->resource = Aura::findResourceBySlug($this->slug);

        // if this post is null redirect to dashboard
        if (is_null($this->resource)) {
            return redirect()->route('aura.dashboard');
        }

        if (! $this->resource::$indexViewEnabled) {
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
