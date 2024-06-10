<div>
    @if ($activeModal)
        <x-aura::dialog wire:model="activeModal">
            <x-aura::dialog.panel>
                @forelse($modals as $id => $modal)
                    @if ($activeModal == $id)
                        @livewire($modal['name'], $modal['arguments'], key($id))
                    @endif
                @empty
                @endforelse
            </x-aura::dialog.panel>
        </x-aura::dialog>
    @endif
</div>
