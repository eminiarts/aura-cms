<template x-teleport="body">
    <div x-on:click.stop="console.log('stop here')">
    <div x-dialog x-model="dialogOpen" style="display: none" class="fixed inset-0 overflow-y-auto z-10 text-left sm:pt-0" >
        <!-- Overlay -->
        <div x-dialog:overlay x-transition:enter.opacity class="fixed inset-0 bg-black/25"></div>

        <!-- Panel -->
        <div  class="fixed inset-y-0 right-0 max-w-lg w-full p-0">
            <div x-dialog:panel x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" class="h-full w-full" x-on:click.outside="$dialog.close()">
                <div class="h-full flex flex-col justify-between bg-white shadow-lg overflow-y-auto">

                    <!-- Close Button -->
                    <div class="absolute top-0 right-0 pt-4 pr-4 z-[2]">
                        <div x-on:click="$dialog.close()"
                            class="cursor-pointer bg-gray-50 rounded-lg p-2 text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                            <span class="sr-only">Close modal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <!-- Panel -->
                    <div class="p-8 flex flex-col h-full">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    @if($footer ?? false)
                    <div class="p-4 flex justify-end space-x-2 bg-gray-100">
                        {{ $footer }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
</template>
