<template x-teleport="body">
    <div x-on:click.stop="console.log('stop here')">
    <div x-dialog x-model="dialogOpen" style="display: none" class="overflow-y-auto fixed inset-0 z-10 text-left sm:pt-0" >
        <!-- Overlay -->
        <div x-dialog:overlay x-transition:enter.opacity class="fixed inset-0 bg-black/25"></div>

        <!-- Panel -->
        <div x-on:click="$dialog.close()" class="fixed inset-y-0 right-0 p-0 w-full max-w-lg">
            <div x-on:click.stop="" x-dialog:panel x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" class="w-full h-full" x-on:click.outside="$dialog.close()">
                <div class="flex overflow-y-auto flex-col justify-between h-full bg-white shadow-lg">

                    <!-- Close Button -->
                    <div class="absolute top-0 right-0 pt-4 pr-4 z-[3]">
                        <div x-on:click="$dialog.close()"
                            class="p-2 text-gray-600 bg-gray-50 rounded-lg cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                            <span class="sr-only">Close modal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <!-- Panel -->
                    <div class="flex flex-col p-8 h-full">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    @if($footer ?? false)
                    <div class="flex justify-end p-4 space-x-2 bg-gray-100">
                        {{ $footer }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
</template>
