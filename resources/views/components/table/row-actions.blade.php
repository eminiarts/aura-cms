{{-- If createInModal is true, open a Modal --}}
@if($this->editInModal)
<a href="#" wire:click.prevent="$emit('openModal', 'aura::post-edit-modal', {{ json_encode(["post" => $row->id, 'type' => $row->getType()]) }})">
    <x-aura::icon icon="edit" />
</a>
@else
<div class="flex space-x-2">
    @can('view', $row)
    <x-aura::tippy text="View" position="top" class="text-sm text-gray-400 bg-white">
        <x-aura::button.transparent :href="$row->viewUrl()" size="xs">
            <x-aura::icon icon="view" size="xs" />
        </x-aura::button.transparent>
    </x-aura::tippy>
    @endcan


    @can('edit', $row)
    <x-aura::tippy text="Edit" position="top" class="text-sm text-gray-400 bg-white">
        <x-aura::button.transparent :href="$row->editUrl()" size="xs">
            <x-aura::icon icon="edit" size="xs" />
        </x-aura::button.transparent>
    </x-aura::tippy>
    @endcan
</div>
@endif
