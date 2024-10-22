@aware(['modalAttributes'])

<template x-teleport="body">
    <div x-on:click.stop="console.log('stop here')">
        <div x-dialog x-model="dialogOpen" style="display: none"
            class="fixed inset-0 overflow-y-auto z-10 text-left pt-[30%] sm:pt-0">

            <!-- Overlay -->
            <div x-dialog:overlay x-transition:enter.opacity class="fixed inset-0 bg-black/25"></div>

            <!-- Panel -->
            <div x-on:click="$dialog.close()" class="flex relative justify-center items-end p-0 min-h-full sm:items-center sm:p-4">
                <div x-on:click.stop="" x-dialog:panel x-transition.in
                    class="overflow-hidden relative w-full bg-white rounded-t-xl shadow-lg dark:bg-gray-800 sm:rounded-b-xl {{ optional($modalAttributes)['modalClasses'] }}">
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
                    <div class="absolute top-0 right-0 pt-4 pr-4 z-[2]">
                        <x-aura::button.transparent tabindex="-1" x-on:click="$dialog.close()">
                            <span class="sr-only">Close modal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </x-aura::button.transparent>
                    </div>

                    <!-- Panel -->
                    <div class="p-6">
                        {{ $slot }}

                        @if ($footer ?? false)
                            <div class="p-4 px-4 py-4 -mx-6 mt-6 -mb-6 bg-gray-100 dark:bg-gray-800">
                                <div class="flex gap-4 justify-end">
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
