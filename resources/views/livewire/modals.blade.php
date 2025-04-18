<div>
    @forelse($modals as $id => $modal)
        <x-aura::dialog wire:model="activeModals.{{ $id }}">

            @if ($modal['modalAttributes']['slideOver'])
                <x-aura::dialog.slideover wire:key="slideover-{{ $id }}">
                    {{-- @dump($activeModals) --}}
                    @livewire($modal['name'], $modal['arguments'], key($id))
                </x-aura::dialog.slideover>
            @else
                <x-aura::dialog.panel :modalAttributes="$modal['modalAttributes']" wire:key="panel-{{ $id }}">
                    @livewire($modal['name'], array_merge($modal['arguments'], ['modalAttributes' => $modal['modalAttributes']]), key($id))
                </x-aura::dialog.panel>
            @endif
        </x-aura::dialog>
    @empty
    @endforelse

    @php
        ray('modals');
        ray($modals);
    @endphp
</div>
