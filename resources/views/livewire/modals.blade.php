<div>
    @forelse($modals as $id => $modal)
        <x-aura::dialog wire:model="activeModals.{{ $id }}">

            @if ($modal['modalAttributes']['slideOver'])
                <x-aura::dialog.slideover>
                    {{-- @dump($activeModals) --}}
                    @livewire($modal['name'], $modal['arguments'], key($id))
                </x-aura::dialog.slideover>
            @else
                <x-aura::dialog.panel>
                    {{-- @dump($activeModals) --}}
                    @livewire($modal['name'], $modal['arguments'], key($id))
                </x-aura::dialog.panel>
            @endif
        </x-aura::dialog>
    @empty
    @endforelse
</div>
