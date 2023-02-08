@props(['key' => 'slideover'])

<div x-data="{ open: @entangle('open').defer, key: '{{ $key }}', init() {
    {{-- console.log($root.getAttribute('wire:id')); --}}

    Livewire.on('openSlideOver', (id, params) => {
        if(id == this.key) {
            @this.activate(params);
        }
    });

} }"  class="flex justify-center">


    <!-- Modal -->
    <div
        x-show="open"
        style="display: none"
        x-on:keydown.escape.prevent.stop="open = false"
        role="dialog"
        aria-modal="true"
        x-id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-10 w-screen"
    >
        <!-- Overlay -->
        <div x-show="open" x-on:click="open = false" x-transition.opacity class="fixed inset-0 bg-black cursor-pointer bg-opacity-20 blur-md"></div>

        <!-- Panel -->
        <div
            x-show="open"
                x-transition:enter="transition ease-in-out transform duration-300"
                x-transition:enter-start="opacity-0 translate-x-[60%]"
                x-transition:enter-end="opacity-100 translate-x-[0%]"
                x-transition:leave="transition ease-in-out transform duration-500"
                x-transition:leave-start="opacity-100 translate-x-[0%]"
                x-transition:leave-end="opacity-0 translate-x-[100%]"
                class="absolute top-0 bottom-0 right-0 flex items-end justify-end w-5/6 max-w-2xl min-h-screen"
        >
            <div
                x-on:click.stop
                x-trap.noscroll.inert="open"
                class="relative w-full h-screen p-12 overflow-y-auto origin-right bg-white dark:bg-gray-900"
            >

            <div class="relative flex items-start justify-end">
                 <!-- Title -->
                <div class="absolute flex items-center ml-3 h-7">
                  <button type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" x-on:click="open = false">
                    <span class="sr-only">Close panel</span>
                    <x-icon icon="close" />
                  </button>
                </div>
              </div>


                {{ $slot }}



            </div>
        </div>
    </div>
</div>
