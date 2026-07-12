@aware(['modalAttributes' => ['modalClasses' => 'max-w-2xl']])

<template x-teleport="body">
    <div x-on:click.stop="">
        <div x-dialog x-model="dialogOpen" style="display: none"
            class="fixed inset-0 overflow-y-auto z-10 text-left pt-[30%] sm:pt-0">

            <!-- Overlay -->
            <div x-dialog:overlay x-transition:enter.opacity class="fixed inset-0 bg-gray-950/30 backdrop-blur-[2px]"></div>

            <!-- Panel -->
            <div x-on:click="$dialog.close()" class="flex relative justify-center items-end p-0 min-h-full sm:items-center sm:p-4">
                <div x-on:click.stop="" x-dialog:panel x-transition.in
                    class="overflow-hidden relative w-full bg-white rounded-t-xl shadow-2xl ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 sm:rounded-b-xl {{ optional($modalAttributes)['modalClasses'] }}">
                    <!-- Mobile: Top "grab" handle... -->
                    <div class="sm:hidden absolute top-[-10px] left-0 right-0 h-[50px]" x-data="{ startY: 0, currentY: 0, moving: false, get distance() { return this.moving ? Math.max(0, this.currentY - this.startY) : 0 } }"
                        x-on:touchstart="moving = true; startY = currentY = $event.touches[0].clientY"
                        x-on:touchmove="currentY = $event.touches[0].clientY"
                        x-on:touchend="if (distance > 100) $dialog.close(); moving = false;"
                        x-effect="$el.parentElement.style.transform = 'translateY('+distance+'px)'">
                        <div class="flex justify-center pt-[12px]">
                            <div class="bg-gray-400 rounded-full w-[10%] h-[5px]"></div>
                        </div>
                    </div>

                    <!-- Close Button -->
                    <div class="absolute top-0 right-0 pt-4 pr-4 z-[3]">
                        <button type="button" tabindex="-1" x-on:click="$dialog.close()"
                            class="flex justify-center items-center w-8 h-8 text-gray-400 rounded-lg transition duration-150 ease-out hover:bg-gray-950/5 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300">
                            <span class="sr-only">Close modal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Panel -->
                    <div class="p-6">
                        {{ $slot }}

                        @if ($footer ?? false)
                            <div class="px-6 py-4 -mx-6 mt-6 -mb-6 border-t border-gray-950/5 bg-gray-50/50 dark:border-white/10 dark:bg-white/[0.02]">
                                <div class="flex gap-3 justify-end">
                                    {{ $footer }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
