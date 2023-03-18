<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BookmarkPage extends Component
{

    public $bookmarks;
    public $site;

    public function mount($site)
    {
        $this->site = $site;
        $this->bookmarks = auth()->user()->getOptionBookmarks();
    }

    public function getIsBookmarkedProperty()
    {
        $bookmarks = auth()->user()->getOptionBookmarks();
        $bookmarkUrls = array_column($bookmarks, 'url');
        $key = array_search($this->site['url'], $bookmarkUrls);

        if ($key !== false) {
            return false;
        } else {
            return true;
        }
    }

    public function toggleBookmark()
    {
        $bookmarks = auth()->user()->getOptionBookmarks();
        $bookmarkUrls = array_column($bookmarks, 'url');
        $key = array_search($this->site['url'], $bookmarkUrls);


        if ($key !== false) {
            unset($bookmarks[$key]);
        } else {
            $bookmarks[] = $this->site;
        }

        // save without keys
        $bookmarks = array_values($bookmarks);

        auth()->user()->updateOption('bookmarks', $bookmarks);
        // dump('toggleBookmark', $bookmarks, $bookmarkUrls, $key);
    }

    public function render()
    {
        return view('aura::livewire.bookmark-page');
    }

}
