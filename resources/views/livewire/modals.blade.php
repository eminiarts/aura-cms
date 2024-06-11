<div>
    @if ($activeModal)

        @forelse($modals as $id => $modal)
            @if ($activeModal == $id)
                <x-aura::dialog wire:model="activeModal">
                    @if ($modal['modalAttributes']['slideOver'])
                        <x-aura::dialog.slideover>
                            @livewire($modal['name'], $modal['arguments'], key($id))
                        </x-aura::dialog.slideover>
                    @else
                        <x-aura::dialog.panel>
                            @livewire($modal['name'], $modal['arguments'], key($id))
                        </x-aura::dialog.panel>
                    @endif
                </x-aura::dialog>
            @endif
        @empty
        @endforelse

    @endif
</div>
