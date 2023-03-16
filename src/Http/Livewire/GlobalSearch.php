<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GlobalSearch extends Component
{
    public $search;

    public $searchResults = [];


    public function render()
    {
        return view('aura::livewire.global-search');
    }

    public function updatedSearch()
    {
        // Get all accessible resources
        $resources = app('aura')::getResources();

        // Initialize search results array

        // filter out flows and flow_logs from resources
        $resources = array_filter($resources, function ($resource) {
            // dump($resource::getSlug());
            return $resource::getSlug() !== 'flow' && $resource::getSlug() !== 'flowlog' && $resource::getSlug() !== 'operation' && $resource::getSlug() !== 'flowoperation' && $resource::getSlug() !== 'operationlog' && $resource::getSlug() !== 'option' && $resource::getSlug() !== 'team' && $resource::getSlug() !== 'user' && $resource::getSlug() !== 'product' ;
        });


        // dd($resources);

        $this->searchResults = [];

        // Search in each resource model
        foreach ($resources as $resource) {
            $model = $resource::query();

            // if model has table, return
            // if ($model->table) {
            //     continue;
            // }

            $results = $model->where('title', 'like', '%' . $this->search . '%')->orWhereHas('meta', function (Builder $query) {
                $query->where('key', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('value', 'LIKE', '%' . $this->search . '%');
            })->get();
            $this->searchResults = array_merge($this->searchResults, $results->toArray());
        }

        // Search in User model
        $userResults = User::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->get();
        $this->searchResults = array_merge($this->searchResults, $userResults->toArray());

        // limit search results to 15
        $this->searchResults = array_slice($this->searchResults, 0, 10);
        // dump($this->searchResults);
        // map searchresults and add View URL to each item
        // dump($this->searchResults);

        $this->searchResults = array_map(function ($item) {
            // return route aura.post.view with slug and id
            if (isset($item['type'])) {
            $item['view_url'] = route('aura.post.view', ['slug' => $item['type'], 'id' => $item['id']]);
            } else {
                $item['view_url'] = route('aura.post.view', ['slug' => 'user', 'id' => $item['id']]);
            }
            return $item;
        }, $this->searchResults);
    }


}
