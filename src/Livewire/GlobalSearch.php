<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class GlobalSearch extends Component
{
    public array $bookmarks = [];

    public $search = '';

    public function getSearchResultsProperty(): array|Collection
    {
        $actor = $this->authorizeUse();

        if (! is_string($this->search) || $this->search === '') {
            return [];
        }

        $actorGate = Gate::forUser($actor);
        $resources = array_filter(app('aura')::getResources(), function ($resource) use ($actorGate): bool {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                return false;
            }

            $model = app($resource);

            if (! $model instanceof Resource || $resource::getGlobalSearch() === false
                || ! $actorGate->allows('viewAny', $model)) {
                return false;
            }

            return ! in_array($resource::getSlug(), [
                'resource',
                'flow',
                'flowlog',
                'operation',
                'flowoperation',
                'operationlog',
                'option',
                'team',
                'user',
                'product',
            ], true);
        });

        $searchResults = collect();

        foreach ($resources as $resource) {
            $model = app($resource);
            $authenticatedUser = auth()->user();

            if (! $authenticatedUser instanceof User
                || User::connectionCacheIdentity($authenticatedUser->getConnection())
                    !== User::connectionCacheIdentity($model->getConnection())
                || ! Gate::allows('viewAny', $model)) {
                continue;
            }

            $searchableFields = $model->getSearchableFields()->pluck('slug');

            if ($searchableFields->isEmpty()) {
                continue;
            }

            $results = $model->newQuery()
                ->select($model->getTable().'.*')
                ->where(function (Builder $query) use ($model, $searchableFields): void {
                    foreach ($searchableFields as $field) {
                        if ($model->isMetaField($field)) {
                            $metaTable = $model->getMetaTable();
                            $metaForeignKey = $model->getMetaForeignKey();

                            $query->orWhereExists(function ($subquery) use ($field, $metaForeignKey, $metaTable, $model): void {
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
                ->get()
                ->filter(fn (Resource $record): bool => $actorGate->allows('view', $record));

            $searchResults->push(...$results);
        }

        /** @var User $configuredUser */
        $configuredUser = app(config('aura.resources.user'));
        $userPrototype = $configuredUser->newInstance();
        $userPrototype->setConnection($actor->getConnectionName());

        if ($actorGate->allows('viewAny', $userPrototype)) {
            $userResults = $userPrototype->newQuery()
                ->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                })
                ->get()
                ->filter(fn (User $user): bool => $actorGate->allows('view', $user));
            $searchResults->push(...$userResults);
        }

        return $searchResults
            ->take(15)
            ->map(function (Resource $item): Resource {
                if ($item instanceof User) {
                    $item['view_url'] = route('aura.user.view', ['id' => $item->getKey()]);
                } else {
                    $item['type'] = $item->getType();
                    $item['view_url'] = route('aura.'.$item->getSlug().'.view', ['id' => $item->getKey()]);
                }

                return $item;
            })
            ->groupBy('type');
    }

    public function hydrate(): void
    {
        $this->authorizeUse();
    }

    public function mount(): void
    {
        $actor = $this->authorizeUse();
        $this->bookmarks = $actor->getOptionBookmarks();
    }

    public function render(): View
    {
        $actor = $this->authorizeUse();
        $this->bookmarks = $actor->getOptionBookmarks();

        return view('aura::livewire.global-search');
    }

    private function authorizeUse(): User
    {
        if (! config('aura.features.global_search')) {
            abort(403, 'Global search is disabled');
        }

        $actor = auth()->user();

        if (! $actor instanceof User) {
            abort(403);
        }

        return $actor;
    }
}
