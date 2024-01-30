<?php

namespace Eminiarts\Aura\Livewire;

use Livewire\Component;

class BookmarkPage extends Component
{
    public $bookmarks;

    public $site;

    public function getIsBookmarkedProperty()
    {
        if (! auth()->check()) {
            return false;
        }

        $bookmarks = auth()->user()->getOptionBookmarks();
        $bookmarkUrls = array_column($bookmarks, 'url');
        $key = array_search($this->site['url'], $bookmarkUrls);

        if ($key !== false) {
            return false;
        } else {
            return true;
        }
    }

    public function mount($site)
    {
        $this->site = $site;

        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        }
    }

    public function render()
    {
        return view('aura::livewire.bookmark-page');
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
}
