<div>
    <div x-show="show" x-data="globalSearch" x-ref="searchContainer" style="display: none;" @search.window="search()" @keydown.escape.window="closeSearch()">
    <div class="fixed inset-0 z-30">
        <div class="absolute inset-0 bg-black/10 backdrop-blur-sm"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-full max-w-2xl p-0 mx-5 bg-white rounded-md shadow-xl dark:bg-gray-900" @click.away="closeSearch()">
                <div class="flex items-center">
                    <div class="px-3">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.25 19.25L15.5 15.5M4.75 11C4.75 7.54822 7.54822 4.75 11 4.75C14.4518 4.75 17.25 7.54822 17.25 11C17.25 14.4518 14.4518 17.25 11 17.25C7.54822 17.25 4.75 14.4518 4.75 11Z"></path>
                        </svg>
                    </div>

                    <input wire:model="search" x-ref="searchField" class="py-4 px-2 focus:outline-none relative block w-full rounded-none rounded-t-md bg-transparent focus:z-10 focus:border-0 focus:!border-none sm:text-sm" style="border: none; box-shadow: none;" aria-autocomplete="list" aria-labelledby="docsearch-label" id="docsearch-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Search Aura" maxlength="64" type="search" @keydown.arrow-up.prevent="selectedIndex > 0 ? selectedIndex-- : selectedIndex = searchResults.length - 1"
                            @keydown.arrow-down.prevent="selectedIndex < searchResults.length - 1 ? selectedIndex++ : selectedIndex = 0"
                        @keydown.enter="openSelectedResult()"
                    >

                    <div>
                        <span @click="closeSearch()" class="cursor-pointer mx-3 text-[10px] text-gray-400 lowercase bg-white shadow-lg rounded border border-gray-100 w-6 h-6 flex items-center justify-center">esc</span>
                    </div>
                </div>

                <div class="p-0 border-t">
                    <div>

                        <ul class="mt-4">
                            <template x-for="(result, index) in searchResults" :key="index">
                                <li class="flex px-4 py-2 border-b cursor-default select-none hover:bg-primary-500 hover:text-white" x-bind:id="'option-' + index" role="option" tabindex="-1"
                                :class="{ 'bg-primary-500 text-white': index === selectedIndex }"
                                >
                                    <div class="w-8 h-8 mr-4 rounded-full shrink-0 bg-primary-200">
                                        {{-- SVG Icon Circle --}}
                                        <svg class="w-full h-full text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path>
                                        </svg>
                                    </div>

                                    <div class="flex-1">
                                        <a x-bind:href="result.view_url">
                                            {{-- x-if result is array --}}

                                                <span x-text="result['type']"></span>: <span x-text="result['id']"></span> <span x-text="result['title']"></span>

                                            <template x-if="result.type == 'user'">
                                                <span x-text="result.type"></span>: <span x-text="result.id"></span> <span x-text="result.name"></span>
                                            </template>
                                        </a>
                                    </div>
                                </li>
                            </template>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
	document.addEventListener('alpine:init', () => {
		Alpine.data('globalSearch', () => ({
            loading: false,
            show: false,

            search: '',

            items: [],
            searchResults: @entangle('searchResults'),

            selectedIndex: -1,

			init() {
				console.log('init search');
                this.$refs.searchContainer.style.display = 'block';
			},

            search() {
                this.show = ! this.show;
                setTimeout(() => {
                    this.$refs.searchField.focus()
                }, 50)
            },
            closeSearch() {
                this.show = false;
            },

            openSelectedResult() {
                this.searchResults[this.selectedIndex].view_url && window.location.replace(this.searchResults[this.selectedIndex].view_url);
            }

		}))

	})
</script>

</div>
