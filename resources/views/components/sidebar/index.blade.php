@props(['heading' => null, 'footer' => null, 'show' => 'rightSidebar'])

<div x-cloak x-show="{{ $show }}" class="flex-shrink-0 w-0 xl:w-96 dark:bg-gray-800">
    <div x-ref="sidebar" class="flex fixed top-0 right-0 z-10 flex-col flex-shrink-0 w-96 h-screen border-l shadow-xl border-gray-400/30 dark:bg-gray-800 dark:border-gray-700 shadow-gray-400 xl:shadow-none">
        <div class="absolute bg-primary-25 dark:bg-gray-800 opacity-50 inset-0 z-[-1]"></div>
        <div class="flex-shrink-0 px-5 h-[4.5rem] flex items-center justify-between border-b border-gray-400/30 dark:border-gray-700">

            @if($heading)
            <div {{ $heading->attributes->class(['']) }}>
                {{ $heading }}
            </div>
            @endif

            <div>
                <x-aura::tippy text="Close" position="left">
                    <span class="cursor-pointer" @click="toggleFilters()">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </span>
                </x-aura::tippy>
            </div>
        </div>

        <div class="overflow-y-auto flex-1 p-5">
            {{ $slot }}
        </div>

        @if($footer)
        <footer {{ $footer->attributes->class(['flex-shrink-0 px-5 h-[4.5rem] flex items-center border-t border-white border-opacity-10 dark:border-gray-700']) }}>
            {{ $footer }}
        </footer>
        @endif

    </div>
</div>
