<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GlobalSearch extends Component
{
    public $search;

    public function render()
    {
        $searchResults = [];

        if (!empty($this->search)) {
            $searchResults = Post::where('title', 'LIKE', '%' . $this->search . '%')->orWhereHas('meta', function (Builder $query) {
                $query->where('key', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('value', 'LIKE', '%' . $this->search . '%');
            })->get();
            // Implement logic for advanced search, using natural language processing, post_meta, post_type, etc.
        }

        return view('aura::livewire.global-search', [
            'searchResults' => $searchResults
        ]);
    }
}
