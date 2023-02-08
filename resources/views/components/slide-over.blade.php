@props(['key' => 'slideover'])

<div x-aura::data="{ open: @entangle('open').defer, key: '{{ $key }}', init() {
    {{-- console.log($root.getAttribute('wire:id')); --}}

    Livewire.on('openSlideOver', (id, params) => {
        if(id == this.key) {
            @this.activate(params);
        }
    });

} }"  class="flex justify-center">


    <!-- Modal -->
    <div
        x-aura::show="open"
        style="display: none"
        x-aura::on:keydown.escape.prevent.stop="open = false"
        role="dialog"
        aria-modal="true"
        x-aura::id="['modal-title']"
        :aria-labelledby="$id('modal-title')"
        class="fixed inset-0 z-10 w-screen"
    >
        <!-- Overlay -->
        <div x-aura::show="open" x-aura::on:click="open = false" x-aura::transition.opacity class="fixed inset-0 bg-black cursor-pointer bg-opacity-20 blur-md"></div>

        <!-- Panel -->
        <div
            x-aura::show="open"
                x-aura::transition:enter="transition ease-in-out transform duration-300"
                x-aura::transition:enter-start="opacity-0 translate-x-aura::[60%]"
                x-aura::transition:enter-end="opacity-100 translate-x-aura::[0%]"
                x-aura::transition:leave="transition ease-in-out transform duration-500"
                x-aura::transition:leave-start="opacity-100 translate-x-aura::[0%]"
                x-aura::transition:leave-end="opacity-0 translate-x-aura::[100%]"
                class="absolute top-0 bottom-0 right-0 flex items-end justify-end w-5/6 max-aura::w-2xl min-h-screen"
        >
            <div
                x-aura::on:click.stop
                x-aura::trap.noscroll.inert="open"
                class="relative w-full h-screen p-12 overflow-y-auto origin-right bg-white dark:bg-gray-900"
            >

            <div class="relative flex items-start justify-end">
                 <!-- Title -->
                <div class="absolute flex items-center ml-3 h-7">
                  <button type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" x-aura::on:click="open = false">
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
