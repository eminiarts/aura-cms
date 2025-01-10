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

    /**
     * Mount the component.
     *
     * @param string|null $slug The resource slug
     */
    public function mount($slug = null)
    {
        // Get the slug from parameter or current route
        $this->slug = $slug ?? explode('.', request()->route()->getName())[1] ?? null;

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
