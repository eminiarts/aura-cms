<div>
    @if (config('aura.features.global_search'))
        <style>
            .search-list-icon svg {
                width: 1.5rem;
                height: 1.5rem;
            }
        </style>
        <div x-show="show" x-data="{
            loading: false,
            show: false,

            search: @entangle('search').live,

            items: [],

            selectedIndex: -1,

            visitedPages: [],

            init() {
                this.$refs.searchContainer.style.display = 'block';
                this.loadVisitedPages();

                this.$watch(() => this.search, () => {
                    this.selectFirst();
                });
            },

            loadVisitedPages() {
                const key = 'visitedPages';
                this.visitedPages = JSON.parse(localStorage.getItem(key)) || [];
            },

            openSearch($event) {
                // Prevent search from opening when typing in input fields
                console.log($event);

                // Check if the event target is defined and has a tagName property
                if ($event.target && $event.target.tagName) {
                    const tagName = $event.target.tagName.toLowerCase();
                    console.log(tagName);

                    if (tagName === 'input' || tagName === 'textarea') {
                        return;
                    }
                }

                console.log('open search');
                this.show = !this.show;
                setTimeout(() => {
                    this.$refs.searchField.focus()
                }, 50)
            },
            closeSearch() {
                this.show = false;
            },

            escapeSearch() {
                if (this.search !== '') {
                    this.search = '';
                    this.selectedIndex = -1;
                } else {
                    this.selectedIndex = -1;
                    this.show = false;
                }
            },

            selectFirst() {
                this.selectedIndex = 0;
            },

            selectPrevious() {
                const items = this.search ? this.$refs.searchList.querySelectorAll('.select-item') : this.$refs.commandList.querySelectorAll('.select-item');
                const length = items.length;

                this.selectedIndex > 0 ? this.selectedIndex-- : this.selectedIndex = length - 1;
            },

            selectNext() {
                const items = this.search ? this.$refs.searchList.querySelectorAll('.select-item') : this.$refs.commandList.querySelectorAll('.select-item');
                const length = items.length;
                this.selectedIndex < length - 1 ? this.selectedIndex++ : this.selectedIndex = 0;
            },

            openSelectedResult(e) {
                if (this.selectedIndex > -1) {
                    if (this.$refs.searchList.children.length > 0) {
                        var link = this.$refs.searchList.querySelectorAll('.select-item')[this.selectedIndex].querySelector('a');
                        link.click();
                    } else {
                        var link = this.$refs.commandList.querySelectorAll('.select-item')[this.selectedIndex].querySelector('a');
                        link.click();
                    }
                }
            },

            openBookmark(index) {
                if (!this.$refs.commandList.querySelectorAll('.bookmark-item')[index - 1]) return;
                const link = this.$refs.commandList.querySelectorAll('.bookmark-item')[index - 1].querySelector('a');
                if (!link) return;
                link.click();
            },

        }" x-ref="searchContainer" style="display: none;" @search.window="openSearch($event)" @keydown.escape.window="escapeSearch()" @keydown.cmd.1.prevent="openBookmark(1)" @keydown.cmd.2.prevent="openBookmark(2)" @keydown.cmd.3.prevent="openBookmark(3)" @keydown.cmd.4.prevent="openBookmark(4)" @keydown.cmd.5.prevent="openBookmark(5)" @keydown.cmd.6.prevent="openBookmark(6)" @keydown.cmd.7.prevent="openBookmark(7)" @keydown.cmd.8.prevent="openBookmark(8)" @keydown.cmd.9.prevent="openBookmark(9)">
            <div class="fixed inset-0 z-30">
                <div class="absolute inset-0 backdrop-blur-sm bg-black/10 dark:bg-black/20"></div>
                <div class="flex absolute inset-0 justify-center items-center">
                    <div class="overflow-hidden p-0 mx-5 w-full max-w-2xl bg-white rounded-md shadow-xl dark:bg-gray-900 dark:border dark:border-gray-700" @click.away="closeSearch()">
                        <div class="flex items-center">
                            <div class="px-3">
                                <svg width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.25 19.25L15.5 15.5M4.75 11C4.75 7.54822 7.54822 4.75 11 4.75C14.4518 4.75 17.25 7.54822 17.25 11C17.25 14.4518 14.4518 17.25 11 17.25C7.54822 17.25 4.75 14.4518 4.75 11Z"></path>
                                </svg>
                            </div>

                            <input x-model.debounce.300ms="search" x-ref="searchField" class="py-4 px-2 focus:outline-none relative block w-full rounded-none rounded-t-md bg-transparent focus:z-10 focus:border-0 focus:!border-none sm:text-sm" style="border: none; box-shadow: none;" aria-autocomplete="list" aria-labelledby="docsearch-label" id="docsearch-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="{{ __('Search') }}" maxlength="64" type="search"

                                   @keydown.arrow-up.prevent="selectPrevious()"
                                   @keydown.arrow-down.prevent="selectNext()"
                                   @keydown.enter="openSelectedResult()">

                            <div class="relative mx-3 w-6 h-6">

                                <span wire:loading.remove @click="closeSearch()" class="cursor-pointer text-[10px] text-gray-400 dark:text-gray-600 lowercase bg-white dark:bg-gray-900 shadow-lg rounded border border-gray-100 dark:border-gray-800 w-6 h-6 flex items-center justify-center">esc</span>

                                <div wire:loading class="absolute top-0 right-0 w-6">
                                    <div role="status" class="mx-auto w-full">
                                        <svg aria-hidden="true" class="w-6 h-6 text-gray-200 animate-spin dark:text-gray-600 fill-primary-500" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor" />
                                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill" />
                                        </svg>
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-2 border-t dark:border-gray-700">
                            <div>

                                {{-- Alpine template if $refs.searchField input length is 0 --}}

                                <template x-if="!search || search == ''">
                                    <div>
                                        <div class="px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 heading-item">
                                            {{ __('Last visited pages') }}
                                        </div>

                                        <ul x-ref="commandList">

                                            <template x-for="(page, index) in visitedPages" :key="'search-item-' + index">
                                                <li class="flex px-2 py-2 rounded-md cursor-pointer select-item"
                                                    :class="{'bg-primary-500 dark:bg-primary-400/30 text-white': index === selectedIndex, 'hover:bg-gray-100 dark:hover:bg-gray-800': index != selectedIndex }">
                                                    <div class="flex justify-center items-center mr-3 rounded-full search-list-icon text-primary-400 shrink-0">
                                                        {{-- SVG Icon Circle --}}
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                                        </svg>
                                                    </div>

                                                    <div class="overflow-hidden flex-1 whitespace-nowrap text-ellipsis">
                                                        <a class="block overflow-hidden text-ellipsis" :href="page.url" x-text="page.title"></a>
                                                    </div>
                                                </li>
                                            </template>

                                            @if(count($this->bookmarks) > 0)
                                                <div class="px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 heading-item">
                                                    {{ __('Bookmarks') }}
                                                </div>
                                            @endif

                                            @foreach ($this->bookmarks as $key => $bookmark)
                                                <li class="flex px-2 py-2 rounded-md cursor-pointer bookmark-item select-item hover:bg-gray-100 focus:bg-gray-200 dark:hover:bg-gray-800 dark:focus:bg-gray-700"
                                                    :class="{'bg-primary-500 dark:bg-primary-400/30 text-white': 5 + {{ $key }} === selectedIndex, 'hover:bg-gray-100 dark:hover:bg-gray-800': 5 + {{ $key }} != selectedIndex }">
                                                    <div class="flex justify-center items-center mr-3 rounded-full search-list-icon text-primary-400 shrink-0">
                                                        {{-- SVG Icon Circle --}}
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                                        </svg>
                                                    </div>

                                                    <div class="overflow-hidden flex-1 whitespace-nowrap text-ellipsis">
                                                        <a class="block overflow-hidden text-ellipsis" href="{{ $bookmark['url'] }}">{{ $bookmark['title'] }}</a>
                                                    </div>

                                                    @if ($key < 10)
                                                        <div>
                                                            <span class="flex-none ml-3 text-xs font-semibold opacity-50 select-none"><kbd class="font-sans">⌘</kbd><kbd class="font-sans">{{ $key + 1 }}</kbd></span>
                                                        </div>
                                                    @endif
                                                </li>
                                            @endforeach



                                        </ul>

                                    </div>
                                </template>


                                <ul class="mt-0" x-ref="searchList" wire:key="searchList">

                                    @php
                                        $i = 0;
                                    @endphp
                                    @forelse($this->searchResults as $key => $group)
                                        <div class="px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200 heading-item">{{ $key ?? 'Other' }}</div>

                                        @foreach ($group as $key => $result)
                                            <li
                                                class="flex px-2 py-2 rounded-md cursor-default select-none hover:bg-gray-100 focus:bg-gray-200 dark:hover:bg-gray-800 dark:focus:bg-gray-700 select-item" id="option-1" role="option" tabindex="-1"
                                                wire:key="search-result-{{ $i }}"
                                                :class="{'bg-primary-500 dark:bg-primary-400/30 text-white': {{ $i }} === selectedIndex, 'hover:bg-gray-100 dark:hover:bg-gray-800': {{ $i }} != selectedIndex }">

                                                <div class="flex justify-center items-center mr-3 rounded-full search-list-icon text-primary-400 shrink-0">
                                                    {{-- SVG Icon Circle --}}
                                                    {!! $result->getIcon() !!}
                                                </div>

                                                <div class="overflow-hidden flex-1 whitespace-nowrap text-ellipsis">
                                                    <a class="block overflow-hidden text-ellipsis" href="{{ route('aura.resource.view', ['slug' => $result->getType(), 'id' => $result->id]) }}">
                                                        #{{ $result->id }} {{ $result->title() }}
                                                    </a>
                                                </div>

                                                <div>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $result->getType()  }}</span>
                                                </div>

                                            </li>
                                            @php
                                                $i++;
                                            @endphp
                                        @endforeach

                                    @empty
                                        @if ($search)
                                            <li class="flex px-2 py-2 text-center text-gray-600 cursor-default select-none">
                                                No results
                                            </li>
                                        @endif
                                    @endforelse


                                </ul>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
