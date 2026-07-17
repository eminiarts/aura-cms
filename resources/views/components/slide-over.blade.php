@props(['key' => 'slideover'])
<div x-data="{ open: @entangle('open'), key: '{{ $key }}', init() {
    $wire.on('openSlideOver', (data) => {
        {{-- console.log('open slide over', data); --}}

        if(data.target == this.key) {
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
        <div x-show="open" x-on:click="open = false" x-transition.opacity class="fixed inset-0 backdrop-blur-[2px] cursor-pointer bg-gray-950/30"></div>

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
                class="overflow-y-auto relative p-8 w-full h-screen bg-white border-l shadow-2xl origin-right border-gray-950/5 ring-1 ring-gray-950/10 dark:bg-gray-800 dark:border-white/10 dark:ring-white/10 sm:p-12"
            >

            <div class="flex relative justify-end items-start">
                 <!-- Title -->
                <div class="flex absolute items-center ml-3 h-7">
                  <button type="button" class="flex justify-center items-center w-8 h-8 text-gray-400 rounded-lg transition duration-150 ease-out hover:bg-gray-950/5 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300" x-on:click="open = false">
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
