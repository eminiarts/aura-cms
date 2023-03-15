<div>
    <div x-show="show" x-data="globalSearch" x-ref="searchContainer" style="display: none;" @search.window="search()">
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

                    <input wire:model="search" x-ref="searchField" class="py-4 px-2 focus:outline-none relative block w-full rounded-none rounded-t-md bg-transparent focus:z-10 focus:border-0 focus:!border-none sm:text-sm" style="border: none; box-shadow: none;" aria-autocomplete="list" aria-labelledby="docsearch-label" id="docsearch-input" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Search Aura" maxlength="64" type="search">

                    <div>
                        <span @click="closeSearch()" class="cursor-pointer mx-3 text-[10px] text-gray-400 lowercase bg-white shadow-lg rounded border border-gray-100 w-6 h-6 flex items-center justify-center">esc</span>
                    </div>
                </div>

                <div class="p-5 border-t">
                    <h4>Projekte</h4>
                    <div>
                        <ul class="mt-4">
                    @foreach($searchResults as $result)
                        <li class="px-4 py-2 cursor-default select-none hover:bg-primary-500 hover:text-white" id="option-1" role="option" tabindex="-1">
                            @if(! is_array($result))
                            <a href="{{ route('aura.post.view', ['slug' => $result->type, 'id' => $result->id]) }}">
                                {{ $result->type }}: {{ $result->id }} {{ $result->title }}
                            </a>
                            @elseif(isset($result['type']))
                            <a href="{{ route('aura.post.view', ['slug' => $result['type'], 'id' => $result['id']]) }}">
                                {{ $result['type'] }}: {{ $result['id'] }} {{ $result['title'] }}
                            </a>
                            @else
                                User #{{ $result['id'] }}: {{ $result['name'] }}
                            @endif

                        </li>
                    @endforeach
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
            }
		}))

	})
</script>

</div>
