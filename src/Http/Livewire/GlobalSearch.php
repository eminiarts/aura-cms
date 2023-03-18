<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GlobalSearch extends Component
{
    public $search = '';

    public $bookmarks;

    public function render()
    {
        $this->bookmarks = auth()->user()->getOptionBookmarks();
        return view('aura::livewire.global-search');
    }

    public function mount() {
        $this->bookmarks = auth()->user()->getOptionBookmarks();
    }

    public function getSearchResultsProperty()
    {
        // if no $this->search return
        if (!$this->search || $this->search === '') {
            return [];
        }
        // dd('getSearchResultsProperty');
        // Get all accessible resources
        $resources = app('aura')::getResources();

        // Initialize search results array

        // filter out flows and flow_logs from resources
        $resources = array_filter($resources, function ($resource) {
            // dump($resource::getSlug());
            return $resource::getSlug() !== 'resource' && $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product' ;
        });


        // dd($resources);

        $searchResults = collect([]);

        // Search in each resource model
        foreach ($resources as $resource) {
            $model = $resource::query();

            $results = $model->where('title', 'like', '%' . $this->search . '%')->orWhereHas('meta', function (Builder $query) {
                $query->where('key', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('value', 'LIKE', '%' . $this->search . '%');
            })->get();

            $searchResults->push(...$results);
        }

        // Search in User model
        $userResults = User::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->get();
        $searchResults->push(...$userResults);

        $searchResults = $searchResults->flatten()->map(function ($item) {
            // return route aura.post.view with slug and id
            if (isset($item->type)) {
                $item['view_url'] = route('aura.post.view', ['slug' => $item->type, 'id' => $item->id]);
            } else {
                $item['view_url'] = route('aura.post.view', ['slug' => 'user', 'id' => $item->id]);
            }
            return $item;
        });

        // group by type
        $searchResults = $searchResults->groupBy('type');

        return $searchResults;
    }


}
