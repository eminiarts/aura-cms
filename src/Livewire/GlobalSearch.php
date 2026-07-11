<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
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

            // Skip any resource the current user is not allowed to view.
            if (! Gate::allows('viewAny', app($resource))) {
                return false;
            }

            return $resource::getSlug() !== 'resource' && $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product';
        });

        $searchResults = collect([]);

        // Search in each resource model
        foreach ($resources as $resource) {
            // if no resource then continue
            if (! $resource) {
                continue;
            }

            $model = app($resource);
            $searchableFields = $model->getSearchableFields()->pluck('slug');

            if ($searchableFields->isEmpty()) {
                continue;
            }

            $results = $resource::query()
                ->select($model->getTable().'.*')
                ->where(function ($query) use ($model, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        if ($model->isMetaField($field)) {
                            $metaTable = $model->getMetaTable();
                            $metaForeignKey = $model->getMetaForeignKey();

                            $query->orWhereExists(function ($subquery) use ($field, $metaForeignKey, $metaTable, $model) {
                                $subquery->selectRaw('1')
                                    ->from($metaTable)
                                    ->whereColumn($model->getQualifiedKeyName(), $metaTable.'.'.$metaForeignKey)
                                    ->where($metaTable.'.metable_type', $model->getMorphClass())
                                    ->where($metaTable.'.key', $field)
                                    ->where($metaTable.'.value', 'like', '%'.$this->search.'%');
                            });
                        } else {
                            $query->orWhere($model->getTable().'.'.$field, 'like', '%'.$this->search.'%');
                        }
                    }
                })
                ->get();

            $searchResults->push(...$results);
        }

        // Search in User model, but only if the current user may view users.
        if (Gate::allows('viewAny', app(User::class))) {
            $userResults = User::where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
                ->get();
            $searchResults->push(...$userResults);
        }

        $searchResults = $searchResults->flatten()->map(function ($item) {
            if ($item instanceof User) {
                $item['view_url'] = route('aura.user.view', ['id' => $item->id]);
            } elseif ($item instanceof Resource) {
                $item['type'] = $item->getType();
                $item['view_url'] = route('aura.'.$item->getSlug().'.view', ['id' => $item->getKey()]);
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
