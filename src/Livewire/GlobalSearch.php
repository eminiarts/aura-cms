<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\User;
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

        // Get all accessible resources
        $resources = app('aura')::getResources();

        // Initialize search results array

        // filter out flows and flow_logs from resources
        $resources = array_filter($resources, function ($resource) {
            if ($resource === null) {
                return false;
            }

            if ($resource::getGlobalSearch() === false) {
                return false;
            }

            return $resource::getSlug() !== 'resource' && $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product';
        });

        $searchResults = collect([]);

        // Search in each resource model
        foreach ($resources as $resource) {
            $model = $resource::query();

            // if no resource then continue
            if (! $resource) {
                continue;
            }

            $searchableFields = app($resource)->getSearchableFields()->pluck('slug');

            $metaFields = $searchableFields->filter(function ($field) use ($resource) {
                // check if it is a meta field
                return app($resource)->isMetaField($field);
            });

            $results = $model->select('posts.*')
                ->leftJoin('meta', function ($join) use ($metaFields, $resource) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', $resource)
                        ->whereIn('meta.key', $metaFields);
                })
                ->where(function ($query) {
                    $query->where('posts.title', 'like', '%'.$this->search.'%')
                        ->orWhere(function ($query) {
                            $query->where('meta.value', 'LIKE', '%'.$this->search.'%');
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
            // return route aura.resource.view with slug and id
            if (isset($item->type)) {
                $item['view_url'] = route('aura.'.strtolower($item->type).'.view', ['id' => $item->id]);
            } else {
                $item['view_url'] = route('aura.user.view', ['id' => $item->id]);
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
        // if global search is disabled, abort with 403
        if (! config('aura.features.global_search')) {
            abort(403, 'Global search is disabled');
        }

        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }
    }

    public function render()
    {
        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }

        return view('aura::livewire.global-search');
    }
}
