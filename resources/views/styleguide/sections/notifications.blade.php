<div class="flex flex-wrap gap-4 mt-6 aura-card">
    <x-aura::button.light x-data x-on:click="$dispatch('notify', { type: 'success', message: 'Changes saved successfully.' })">
        {{ __('Success toast') }}
    </x-aura::button.light>
    <x-aura::button.danger x-data x-on:click="$dispatch('notify', { type: 'error', message: 'Something went wrong.' })">
        {{ __('Error toast') }}
    </x-aura::button.danger>
</div>

<div class="grid gap-4 p-6 mt-4 bg-gray-50 rounded-xl ring-1 md:grid-cols-2 dark:bg-white/5 ring-gray-950/10 dark:ring-white/10">
    @foreach ([
        'success' => ['message' => __('Changes saved successfully.'), 'icon' => 'text-green-600 bg-green-100 dark:bg-green-500/15 dark:text-green-400', 'path' => 'M5 13L9.5 17.5L19 7', 'bar' => 'bg-primary-500/80 dark:bg-primary-400/80'],
        'error' => ['message' => __('Something went wrong.'), 'icon' => 'text-red-600 bg-red-100 dark:bg-red-500/15 dark:text-red-400', 'path' => 'M6 18L18 6M6 6l12 12', 'bar' => 'bg-red-500/80 dark:bg-red-400/80'],
    ] as $type => $toast)
        <div data-toast-specimen="{{ $type }}">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ $type }} {{ __('(static)') }}</span>
            <div class="mt-3 w-full max-w-sm bg-white rounded-xl ring-1 shadow-lg ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">
                <div class="overflow-hidden rounded-xl">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="flex justify-center items-center w-6 h-6 rounded-full {{ $toast['icon'] }}">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="{{ $toast['path'] }}" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="flex-1 ml-3 w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $toast['message'] }}</p>
                            </div>
                            <div class="flex flex-shrink-0 ml-4">
                                <button type="button" class="inline-flex p-1 -m-1 text-gray-400 rounded-md transition duration-150 ease-out hover:bg-gray-950/5 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <span class="sr-only">{{ __('Dismiss notification') }}</span>
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-0.5 bg-transparent">
                        <div class="absolute left-0 h-0.5 {{ $toast['bar'] }}" style="width: 65%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
