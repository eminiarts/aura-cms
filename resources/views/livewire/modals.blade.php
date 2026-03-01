<div>
    @forelse($modals as $id => $modal)
        @if($modal['active'])
            {{-- Overlay --}}
            <div class="fixed inset-0 w-screen h-screen bg-black/25" style="z-index: 99;" wire:click="closeModal('{{ $id }}')"></div>

            {{-- Modal Panel --}}
            <div class="fixed inset-0 w-screen h-screen overflow-y-auto flex items-end sm:items-center justify-center p-0 sm:p-4 pt-[30%] sm:pt-0"
                 style="z-index: 100;"
                 wire:key="modal-{{ $id }}">
                <div class="relative w-full bg-white rounded-t-xl sm:rounded-b-xl shadow-lg dark:bg-gray-800 {{ $modal['modalAttributes']['modalClasses'] ?? 'max-w-4xl' }}"
                     wire:click.stop="">

                    {{-- Close Button --}}
                    <div class="absolute top-0 right-0 pt-4 pr-4" style="z-index: 3;">
                        <button type="button" wire:click="closeModal('{{ $id }}')"
                            class="inline-flex text-gray-500 dark:text-gray-200 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-700 px-4 py-2.5 text-sm font-semibold rounded-lg">
                            <span class="sr-only">Close modal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        @livewire($modal['name'], array_merge($modal['arguments'] ?? [], ['modalAttributes' => $modal['modalAttributes']]), key($id))
                    </div>
                </div>
            </div>
        @endif
    @empty
    @endforelse

    @if(collect($modals)->where('active', true)->count() > 0)
        <style>
            .aura-sidebar-bg, .aura-navigation > .relative { visibility: hidden !important; }
        </style>
    @endif
</div>
