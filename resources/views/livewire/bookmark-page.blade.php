<div class="text-gray-300 dark:text-gray-600">
    @if(config('aura.features.bookmarks') && config('aura.features.global_search'))
    @php
        // Icon-only toggle: the action is named in the tooltip and for screen readers.
        $bookmarkLabel = $this->isBookmarked ? __('Remove bookmark') : __('Bookmark this page');
    @endphp
    {{-- Keyed by state, so a toggle swaps the element and Alpine starts the tooltip with the new label. --}}
    <div x-data="{ isBookmarked: {{ $this->isBookmarked ? 'true' : 'false' }} }" class="ml-2" wire:key="bookmark-toggle-{{ $this->isBookmarked ? 'on' : 'off' }}">
        <x-aura::tippy :text="$bookmarkLabel" position="bottom">
        <button wire:click="toggleBookmark"
                type="button"
                aria-label="{{ $bookmarkLabel }}"
                aria-pressed="{{ $this->isBookmarked ? 'true' : 'false' }}"
                class="flex justify-center items-center w-6 h-6 rounded transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                :class="{'text-primary-600 dark:text-primary-400': isBookmarked, 'text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400': !isBookmarked }">

            {{-- 16px, the size of the trail's home icon beside it. --}}
            @if(!$this->isBookmarked)
                <x-aura::icon.bookmark class="w-4 h-4" />
            @else
                <x-aura::icon.bookmarked class="w-4 h-4" />
            @endif

        </button>
        </x-aura::tippy>
    </div>
    @endif
</div>
