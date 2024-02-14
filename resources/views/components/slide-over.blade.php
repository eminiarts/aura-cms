@props(['key' => 'slideover'])
<div x-data="{ open: @entangle('open'), key: '{{ $key }}', init() {
    Livewire.on('openSlideOver', (data) => {
        {{-- console.log('open slide over', data); --}}

        if(data.component == this.key) {
            @this.activate(data.parameters);
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
        <div x-show="open" x-on:click="open = false" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-20 blur-md cursor-pointer"></div>

        <!-- Panel -->
        <div
            x-show="open"
                x-transition:enter="transition ease-in-out transform duration-300"
                x-transition:enter-start="opacity-0 translate-x-[60%]"
                x-transition:enter-end="opacity-100 translate-x-[0%]"
                x-transition:leave="transition ease-in-out transform duration-500"
                x-transition:leave-start="opacity-100 translate-x-[0%]"
                x-transition:leave-end="opacity-0 translate-x-[100%]"
                class="flex absolute top-0 right-0 bottom-0 justify-end items-end w-5/6 max-w-2xl min-h-screen"
        >
            <div
                x-on:click.stop
                x-aura::trap.noscroll.inert="open"
                class="overflow-y-auto relative p-12 w-full h-screen bg-white origin-right dark:bg-gray-900"
            >

            <div class="flex relative justify-end items-start">
                 <!-- Title -->
                <div class="flex absolute items-center ml-3 h-7">
                  <button type="button" class="text-gray-400 rounded-md dark:text-gray-400 dark:hover:text-gray-300 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900" x-on:click="open = false">
                    <span class="sr-only">Close panel</span>
                    <x-aura::icon icon="close" />
                  </button>
                </div>
              </div>


                {{ $slot }}



            </div>
        </div>
    </div>
</div>
