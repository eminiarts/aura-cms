<div class="text-gray-300">
    @if(config('aura.features.bookmarks') && config('aura.features.global_search'))
    <button wire:click="toggleBookmark" class="flex justify-center items-center ml-2 w-6 h-6 focus:outline-none" :class="{'text-primary-600': {{ $this->isBookmarked }}, 'text-gray-300': !{{ $this->isBookmarked }} }">

        @if(!$this->isBookmarked)
            <x-aura::icon.bookmark class="w-5 h-5" />
        @else
            <x-aura::icon.bookmarked class="w-5 h-5" />
        @endif

    </button>
    @endif
</div>
