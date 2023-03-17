<div>
    <button wire:click="toggleBookmark" class="focus:outline-none" :class="{'text-primary-600': {{ $this->isBookmarked ? 'true' : 'false' }}, 'text-gray-200': {{ !$this->isBookmarked ? 'true' : 'false' }} }">
        {{-- svg bookmark --}}
        <svg class="w-5 h-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M5 19l7-7 7 7"></path>
        </svg>
    </button>
</div>
