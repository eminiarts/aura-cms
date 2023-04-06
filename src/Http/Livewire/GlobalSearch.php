<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Livewire\Component;

class GlobalSearch extends Component
{
    public $bookmarks;

    public $search = '';

    public function getSearchResultsProperty()
    {
        // if no $this->search return
        if (! $this->search || $this->search === '') {
            return [];
        }
        // dd('getSearchResultsProperty');
        // Get all accessible resources
        $resources = app('aura')::getResources();

        // Initialize search results array

        // filter out flows and flow_logs from resources
        $resources = array_filter($resources, function ($resource) {
            // dump($resource::getSlug());
            return $resource::getSlug() !== 'resource' && $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product';
        });

        // dd($resources);

        $searchResults = collect([]);

        // Search in each resource model
        foreach ($resources as $resource) {
            $model = $resource::query();

            // if no resource then continue
            if (! $resource) {
                continue;
            }

            if (app($resource)->getGlobalSearch() === false) {
                continue;
            }
            // dd($resource);

            // ray('hier 2222', app($resource)->getSearchableFields(), $resource::getSlug());

            $searchableFields = app($resource)->getSearchableFields()->pluck('slug');

            // ray($searchableFields);

            $metaFields = $searchableFields->filter(function ($field) use ($resource) {
                // check if it is a meta field
                return app($resource)->isMetaField($field);
            });

            // ray($metaFields);

            $results = $model->select('posts.*')
                ->leftJoin('post_meta', function ($join) use ($metaFields) {
                    $join->on('posts.id', '=', 'post_meta.post_id')
                        ->whereIn('post_meta.key', $metaFields);
                })
                ->where(function ($query) {
                    $query->where('posts.title', 'like', $this->search.'%')
                        ->orWhere(function ($query) {
                            $query->where('post_meta.value', 'LIKE', $this->search.'%');
                        });
                })
                ->distinct()
                ->get();

            $searchResults->push(...$results);
        }

        // Search in User model
        $userResults = User::where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
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

        // limit to 15
        $searchResults = $searchResults->take(15);

        // group by type
        $searchResults = $searchResults->groupBy('type');

        return $searchResults;
    }

    public function mount()
    {
        $this->bookmarks = auth()->user()->getOptionBookmarks();
    }

    public function render()
    {
        $this->bookmarks = auth()->user()->getOptionBookmarks();

        return view('aura::livewire.global-search');
    }
}
